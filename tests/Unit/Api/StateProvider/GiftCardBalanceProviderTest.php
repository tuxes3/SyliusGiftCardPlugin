<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Api\StateProvider;

use ApiPlatform\Metadata\GetCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Api\StateProvider\GiftCardBalanceProvider;
use Setono\SyliusGiftCardPlugin\Model\GiftCard;
use Setono\SyliusGiftCardPlugin\Model\GiftCardBalanceCollection;
use Setono\SyliusGiftCardPlugin\Repository\GiftCardRepositoryInterface;

final class GiftCardBalanceProviderTest extends TestCase
{
    #[Test]
    public function it_returns_the_balance_of_the_enabled_gift_cards_per_currency(): void
    {
        $giftCards = [];
        foreach ([['EUR', 25], ['EUR', 56], ['USD', 31], ['USD', 84], ['USD', 86]] as [$currencyCode, $amount]) {
            $giftCard = new GiftCard();
            $giftCard->setCurrencyCode($currencyCode);
            $giftCard->setAmount($amount);
            $giftCards[] = $giftCard;
        }

        $giftCardRepository = $this->createMock(GiftCardRepositoryInterface::class);
        $giftCardRepository->method('findEnabled')->willReturn($giftCards);

        $balance = (new GiftCardBalanceProvider($giftCardRepository))->provide(new GetCollection());

        self::assertEquals(GiftCardBalanceCollection::createFromGiftCards($giftCards), $balance);
        self::assertCount(2, $balance);
    }
}
