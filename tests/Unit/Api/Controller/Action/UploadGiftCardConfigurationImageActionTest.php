<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Api\Controller\Action;

use ApiPlatform\Metadata\IriConverterInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Api\Controller\Action\UploadGiftCardConfigurationImageAction;
use Setono\SyliusGiftCardPlugin\Model\GiftCardConfiguration;
use Setono\SyliusGiftCardPlugin\Model\GiftCardConfigurationImage;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final class UploadGiftCardConfigurationImageActionTest extends TestCase
{
    #[Test]
    public function it_uploads_gift_card_configuration_image(): void
    {
        $file = new UploadedFile(__DIR__ . '/file.jpg', 'file.jpg');
        $owner = new GiftCardConfiguration();

        $imageUploader = $this->createMock(ImageUploaderInterface::class);
        $imageUploader->expects(self::once())->method('upload');

        $action = $this->createAction(new GiftCardConfigurationImage(), $owner, $imageUploader);
        $returnedImage = $action($this->createRequest('background', 'super-iri', $file));

        self::assertSame($owner, $returnedImage->getOwner());
        self::assertSame('background', $returnedImage->getType());
        self::assertSame($file, $returnedImage->getFile());
    }

    #[Test]
    public function it_takes_type_and_owner_from_the_query_string(): void
    {
        $file = new UploadedFile(__DIR__ . '/file.jpg', 'file.jpg');
        $owner = new GiftCardConfiguration();
        $request = new Request(['type' => 'background', 'owner' => 'super-iri'], [], [], [], ['file' => $file]);

        $returnedImage = $this->createAction(new GiftCardConfigurationImage(), $owner)($request);

        self::assertSame($owner, $returnedImage->getOwner());
    }

    #[Test]
    public function it_throws_error_if_image_type_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createAction(new GiftCardConfigurationImage(), new GiftCardConfiguration())($this->createRequest(null, 'super-iri'));
    }

    #[Test]
    public function it_throws_error_if_owner_iri_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createAction(new GiftCardConfigurationImage(), new GiftCardConfiguration())($this->createRequest('background', null));
    }

    #[Test]
    public function it_throws_error_if_owner_is_not_a_gift_card_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createAction(new GiftCardConfigurationImage(), new \stdClass())($this->createRequest('background', 'super-iri'));
    }

    #[Test]
    public function it_deletes_old_image_of_same_type(): void
    {
        $oldImage = new GiftCardConfigurationImage();
        $oldImage->setType('background');
        $owner = new GiftCardConfiguration();
        $owner->addImage($oldImage);

        $repository = $this->createMock(RepositoryInterface::class);
        $repository->expects(self::once())->method('remove')->with($oldImage);

        $image = new GiftCardConfigurationImage();
        $this->createAction($image, $owner, null, $repository)($this->createRequest('background', 'super-iri'));

        self::assertSame([$image], $owner->getImagesByType('background')->getValues());
    }

    private function createAction(
        GiftCardConfigurationImage $image,
        object $owner,
        ?ImageUploaderInterface $imageUploader = null,
        ?RepositoryInterface $repository = null,
    ): UploadGiftCardConfigurationImageAction {
        $factory = $this->createMock(FactoryInterface::class);
        $factory->method('createNew')->willReturn($image);

        $iriConverter = $this->createMock(IriConverterInterface::class);
        $iriConverter->method('getResourceFromIri')->with('super-iri')->willReturn($owner);

        return new UploadGiftCardConfigurationImageAction(
            $factory,
            $repository ?? $this->createMock(RepositoryInterface::class),
            $imageUploader ?? $this->createMock(ImageUploaderInterface::class),
            $iriConverter,
        );
    }

    private function createRequest(?string $type, ?string $owner, ?UploadedFile $file = null): Request
    {
        return new Request([], array_filter(['type' => $type, 'owner' => $owner]), [], [], ['file' => $file ?? new UploadedFile(__DIR__ . '/file.jpg', 'file.jpg')]);
    }
}
