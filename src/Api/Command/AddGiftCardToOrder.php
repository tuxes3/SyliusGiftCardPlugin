<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\Command;

use Setono\SyliusGiftCardPlugin\Api\Attribute\GiftCardCodeAware;

#[GiftCardCodeAware]
class AddGiftCardToOrder implements GiftCardCodeAwareInterface
{
    public function __construct(public string $orderTokenValue, public ?string $giftCardCode = null)
    {
    }

    public function getGiftCardCode(): ?string
    {
        return $this->giftCardCode;
    }

    public function setGiftCardCode(?string $giftCardCode): void
    {
        $this->giftCardCode = $giftCardCode;
    }
}
