<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Api\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Api\Command\AddItemToCart;
use Setono\SyliusGiftCardPlugin\Api\Metadata\AddItemToCartInputResourceMetadataCollectionFactory;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart as SyliusAddItemToCart;

final class AddItemToCartInputResourceMetadataCollectionFactoryTest extends TestCase
{
    #[Test]
    public function it_replaces_the_input_of_the_sylius_add_item_to_cart_operation(): void
    {
        $operations = $this->createOperations('Order')[0]->getOperations();

        self::assertSame(AddItemToCart::class, $this->findOperation($operations, AddItemToCartInputResourceMetadataCollectionFactory::OPERATION_NAME)->getInput()['class']);
        self::assertSame(SyliusAddItemToCart::class, $this->findOperation($operations, 'other_operation')->getInput()['class']);
    }

    #[Test]
    public function it_keeps_an_input_that_was_already_replaced(): void
    {
        $collection = $this->createOperations('Order', 'App\\Command\\AddItemToCart');

        self::assertSame('App\\Command\\AddItemToCart', $this->findOperation($collection[0]->getOperations(), AddItemToCartInputResourceMetadataCollectionFactory::OPERATION_NAME)->getInput()['class']);
    }

    private function createOperations(string $resourceClass, string $input = SyliusAddItemToCart::class): ResourceMetadataCollection
    {
        $operations = [
            AddItemToCartInputResourceMetadataCollectionFactory::OPERATION_NAME => new Post(input: ['class' => $input, 'name' => 'AddItemToCart']),
            'other_operation' => new Post(input: ['class' => SyliusAddItemToCart::class, 'name' => 'AddItemToCart']),
        ];

        $decorated = $this->createMock(ResourceMetadataCollectionFactoryInterface::class);
        $decorated->method('create')->willReturn(new ResourceMetadataCollection($resourceClass, [new ApiResource(operations: $operations)]));

        return (new AddItemToCartInputResourceMetadataCollectionFactory($decorated))->create($resourceClass);
    }

    private function findOperation(Operations $operations, string $name): Post
    {
        foreach ($operations as $operationName => $operation) {
            if ($operationName === $name) {
                self::assertInstanceOf(Post::class, $operation);

                return $operation;
            }
        }

        self::fail(sprintf('Operation "%s" not found', $name));
    }
}
