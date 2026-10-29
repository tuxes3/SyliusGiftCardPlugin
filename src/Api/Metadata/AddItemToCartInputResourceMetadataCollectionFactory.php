<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\Metadata;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Setono\SyliusGiftCardPlugin\Api\Command\AddItemToCart;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart as SyliusAddItemToCart;

/**
 * Lets the Sylius "add item to cart" operation take the gift card amount and message.
 *
 * Done here instead of redefining the operation in a resource file, because Sylius merges
 * operations of the same name in the order the files are loaded, which depends on the
 * order of the bundles.
 */
final class AddItemToCartInputResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public const OPERATION_NAME = 'sylius_api_shop_order_order_item_post';

    public function __construct(private readonly ResourceMetadataCollectionFactoryInterface $decorated)
    {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            $operations = $resourceMetadata->getOperations();
            if (null === $operations || !$operations->has(self::OPERATION_NAME)) {
                continue;
            }

            foreach ($operations as $name => $operation) {
                if ($name === self::OPERATION_NAME && $this->hasSyliusInput($operation)) {
                    $operations->add($name, $operation->withInput(['class' => AddItemToCart::class, 'name' => 'AddItemToCart']));
                }
            }

            $resourceMetadataCollection[$key] = $resourceMetadata->withOperations($operations);
        }

        return $resourceMetadataCollection;
    }

    private function hasSyliusInput(Operation $operation): bool
    {
        $input = $operation->getInput();
        $class = is_array($input) ? ($input['class'] ?? null) : $input;

        return $class === SyliusAddItemToCart::class;
    }
}
