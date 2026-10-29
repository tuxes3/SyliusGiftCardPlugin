<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\Command;

use Sylius\Bundle\ApiBundle\Attribute\OrderTokenValueAware;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart as BaseAddItemToCart;

// Attributes are not inherited, so the order token from the URI needs its own marker
#[OrderTokenValueAware]
class AddItemToCart extends BaseAddItemToCart
{
    public function __construct(string $orderTokenValue, string $productVariantCode, int $quantity, protected ?int $amount = null, protected ?string $customMessage = null)
    {
        parent::__construct($orderTokenValue, $productVariantCode, $quantity);
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getCustomMessage(): ?string
    {
        return $this->customMessage;
    }
}
