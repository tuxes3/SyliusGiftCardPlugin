<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Api\StateProcessor;

use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Api\StateProcessor\GiftCardPersistProcessor;
use Setono\SyliusGiftCardPlugin\Model\GiftCard;
use Setono\SyliusGiftCardPlugin\Model\GiftCardInterface;

final class GiftCardPersistProcessorTest extends TestCase
{
    #[Test]
    public function it_marks_the_gift_card_as_created_through_the_api_before_persisting_it(): void
    {
        $giftCard = new GiftCard();
        $operation = new Post();

        $persistProcessor = $this->createMock(ProcessorInterface::class);
        $persistProcessor
            ->expects(self::once())
            ->method('process')
            ->with(self::callback(static fn (GiftCardInterface $data): bool => $data->getOrigin() === GiftCardInterface::ORIGIN_API), $operation, ['code' => 'abc'], ['context' => true])
            ->willReturn($giftCard);

        $result = (new GiftCardPersistProcessor($persistProcessor))->process($giftCard, $operation, ['code' => 'abc'], ['context' => true]);

        self::assertSame($giftCard, $result);
        self::assertSame(GiftCardInterface::ORIGIN_API, $giftCard->getOrigin());
    }

    #[Test]
    public function it_passes_other_data_through_untouched(): void
    {
        $data = new \stdClass();

        $persistProcessor = $this->createMock(ProcessorInterface::class);
        $persistProcessor->expects(self::once())->method('process')->with($data)->willReturn($data);

        self::assertSame($data, (new GiftCardPersistProcessor($persistProcessor))->process($data, new Post()));
    }
}
