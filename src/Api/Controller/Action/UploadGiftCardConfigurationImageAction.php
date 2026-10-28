<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\Controller\Action;

use ApiPlatform\Metadata\IriConverterInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCardConfigurationImageInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCardConfigurationInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

final class UploadGiftCardConfigurationImageAction
{
    public function __construct(
        private readonly FactoryInterface $giftCardConfigurationImageFactory,
        private readonly RepositoryInterface $giftCardConfigurationImageRepository,
        private readonly ImageUploaderInterface $imageUploader,
        private readonly IriConverterInterface $iriConverter,
    ) {
    }

    public function __invoke(Request $request): GiftCardConfigurationImageInterface
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('file');

        /** @var GiftCardConfigurationImageInterface $image */
        $image = $this->giftCardConfigurationImageFactory->createNew();
        $image->setFile($file);

        /** @var string $imageType */
        $imageType = $request->request->get('type') ?? $request->query->get('type');
        Assert::notEmpty($imageType);

        $image->setType($imageType);

        $ownerIri = $request->request->get('owner') ?? $request->query->get('owner');
        Assert::stringNotEmpty($ownerIri);

        $owner = $this->getOwner($ownerIri);

        $oldImages = $owner->getImagesByType($imageType);
        foreach ($oldImages as $oldImage) {
            $owner->removeImage($oldImage);
            $this->giftCardConfigurationImageRepository->remove($oldImage);
        }
        $owner->addImage($image);

        $this->imageUploader->upload($image);

        return $image;
    }

    private function getOwner(string $ownerIri): GiftCardConfigurationInterface
    {
        $owner = $this->iriConverter->getResourceFromIri($ownerIri);
        Assert::isInstanceOf($owner, GiftCardConfigurationInterface::class);

        return $owner;
    }
}
