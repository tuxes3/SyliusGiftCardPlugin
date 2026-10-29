<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\StateProvider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCardBalanceCollection;
use Setono\SyliusGiftCardPlugin\Repository\GiftCardRepositoryInterface;

/**
 * @implements ProviderInterface<GiftCardBalanceCollection>
 */
final class GiftCardBalanceProvider implements ProviderInterface
{
    public function __construct(private readonly GiftCardRepositoryInterface $giftCardRepository)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GiftCardBalanceCollection
    {
        return GiftCardBalanceCollection::createFromGiftCards($this->giftCardRepository->findEnabled());
    }
}
