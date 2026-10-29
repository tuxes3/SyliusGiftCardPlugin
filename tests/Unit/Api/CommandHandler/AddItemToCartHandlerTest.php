<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Api\CommandHandler;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Api\Command\AddItemToCart as SetonoSyliusGiftCardAddItemToCart;
use Setono\SyliusGiftCardPlugin\Api\CommandHandler\AddItemToCartHandler;
use Setono\SyliusGiftCardPlugin\Factory\GiftCardFactoryInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCard;
use Setono\SyliusGiftCardPlugin\Tests\Application\Model\Order;
use Setono\SyliusGiftCardPlugin\Tests\Application\Model\OrderItem;
use Setono\SyliusGiftCardPlugin\Tests\Application\Model\OrderItemUnit;
use Setono\SyliusGiftCardPlugin\Tests\Application\Model\Product;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart as SyliusAddItemToCart;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\Exception\UnprocessableCartException;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariant;
use Sylius\Component\Core\Model\ShopUser;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AddItemToCartHandlerTest extends TestCase
{
    private OrderRepositoryInterface&MockObject $orderRepository;

    private ProductVariantRepositoryInterface&MockObject $productVariantRepository;

    private OrderModifierInterface&MockObject $orderModifier;

    private CartItemFactoryInterface&MockObject $cartItemFactory;

    private GiftCardFactoryInterface&MockObject $giftCardFactory;

    private EntityManagerInterface&MockObject $giftCardManager;

    private UserContextInterface&MockObject $userContext;

    /** @var list<SyliusAddItemToCart> */
    private array $delegated = [];

    private Order $cart;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->productVariantRepository = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->orderModifier = $this->createMock(OrderModifierInterface::class);
        $this->cartItemFactory = $this->createMock(CartItemFactoryInterface::class);
        $this->giftCardFactory = $this->createMock(GiftCardFactoryInterface::class);
        $this->giftCardManager = $this->createMock(EntityManagerInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);

        $this->cart = new Order();
        $this->cart->setTokenValue('token');
        $this->orderRepository->method('findCartByTokenValue')->willReturnCallback(fn (string $token): ?Order => $token === 'token' ? $this->cart : null);
    }

    #[Test]
    public function it_adds_a_gift_card_with_the_chosen_amount_and_message(): void
    {
        $this->givenVariant(giftCard: true, amountConfigurable: true);
        $cartItem = $this->givenCartItem();

        $giftCard = new GiftCard();
        $this->giftCardFactory->expects(self::once())->method('createFromOrderItemUnitAndCart')->willReturn($giftCard);
        $this->giftCardManager->expects(self::once())->method('persist')->with($giftCard);
        $this->orderModifier->expects(self::once())->method('addToOrder')->with($this->cart, $cartItem);

        $cart = $this->createHandler()(new SetonoSyliusGiftCardAddItemToCart('token', 'variant', 1, 1500, 'Custom message'));

        self::assertSame($this->cart, $cart);
        self::assertSame(1500, $cartItem->getUnitPrice());
        self::assertTrue($cartItem->isImmutable());
        self::assertSame('Custom message', $giftCard->getCustomMessage());
        self::assertSame([], $this->delegated);
    }

    #[Test]
    public function it_keeps_the_price_of_a_gift_card_with_a_fixed_amount(): void
    {
        $this->givenVariant(giftCard: true, amountConfigurable: false);
        $cartItem = $this->givenCartItem();
        $this->giftCardFactory->method('createFromOrderItemUnitAndCart')->willReturn(new GiftCard());

        $this->createHandler()(new SetonoSyliusGiftCardAddItemToCart('token', 'variant', 1, 1500));

        self::assertFalse($cartItem->isImmutable());
        self::assertSame([], $this->delegated);
    }

    #[Test]
    public function it_leaves_other_products_to_the_sylius_handler(): void
    {
        $this->givenVariant(giftCard: false, amountConfigurable: false);
        $this->orderModifier->expects(self::never())->method('addToOrder');

        $command = new SetonoSyliusGiftCardAddItemToCart('token', 'variant', 1);
        $this->createHandler()($command);

        self::assertSame([$command], $this->delegated);
    }

    #[Test]
    public function it_leaves_the_sylius_command_to_the_sylius_handler(): void
    {
        $this->givenVariant(giftCard: true, amountConfigurable: true);

        $command = new SyliusAddItemToCart('token', 'variant', 1);
        $this->createHandler()($command);

        self::assertSame([$command], $this->delegated);
    }

    #[Test]
    public function it_rejects_an_unknown_cart(): void
    {
        $this->givenVariant(giftCard: true, amountConfigurable: true);

        $this->expectException(UnprocessableCartException::class);

        $this->createHandler()(new SetonoSyliusGiftCardAddItemToCart('unknown', 'variant', 1, 1500));
    }

    #[Test]
    public function it_hides_the_cart_of_another_customer(): void
    {
        $this->givenVariant(giftCard: true, amountConfigurable: true);
        $owner = $this->createMock(Customer::class);
        $owner->method('getId')->willReturn(1);
        $owner->method('getUser')->willReturn(new ShopUser());
        $this->cart->setCustomerWithAuthorization($owner);

        $otherCustomer = $this->createMock(Customer::class);
        $otherCustomer->method('getId')->willReturn(2);
        $otherUser = new ShopUser();
        $otherUser->setCustomer($otherCustomer);
        $this->userContext->method('getUser')->willReturn($otherUser);

        $this->expectException(NotFoundHttpException::class);

        $this->createHandler()(new SetonoSyliusGiftCardAddItemToCart('token', 'variant', 1, 1500));
    }

    private function givenVariant(bool $giftCard, bool $amountConfigurable): void
    {
        $product = new Product();
        $product->setGiftCard($giftCard);
        $product->setGiftCardAmountConfigurable($amountConfigurable);
        $variant = new ProductVariant();
        $variant->setProduct($product);

        $this->productVariantRepository->method('findOneBy')->with(['code' => 'variant'])->willReturn($variant);
    }

    private function givenCartItem(): OrderItem
    {
        $cartItem = new OrderItem();
        $cartItem->addUnit(new OrderItemUnit($cartItem));
        $this->cartItemFactory->method('createNew')->willReturn($cartItem);

        return $cartItem;
    }

    private function createHandler(): AddItemToCartHandler
    {
        return new AddItemToCartHandler(
            function (SyliusAddItemToCart $command): OrderInterface {
                $this->delegated[] = $command;

                return $this->cart;
            },
            $this->orderRepository,
            $this->productVariantRepository,
            $this->orderModifier,
            $this->cartItemFactory,
            $this->createMock(OrderItemQuantityModifierInterface::class),
            $this->giftCardFactory,
            $this->giftCardManager,
            $this->userContext,
        );
    }
}
