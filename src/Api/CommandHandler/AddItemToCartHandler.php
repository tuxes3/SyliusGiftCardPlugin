<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\CommandHandler;

use Doctrine\ORM\EntityManagerInterface;
use Setono\SyliusGiftCardPlugin\Api\Command\AddItemToCart as SetonoSyliusGiftCardAddItemToCart;
use Setono\SyliusGiftCardPlugin\Factory\GiftCardFactoryInterface;
use Setono\SyliusGiftCardPlugin\Model\OrderItemUnitInterface;
use Setono\SyliusGiftCardPlugin\Model\ProductInterface;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart as SyliusAddItemToCart;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\Exception\ProductVariantUnprocessableException;
use Sylius\Bundle\ApiBundle\Exception\UnprocessableCartException;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

/**
 * Decorates the Sylius handler: gift cards get their amount, message and gift card entities,
 * every other product is added by the Sylius handler
 */
final class AddItemToCartHandler
{
    /**
     * @param callable(SyliusAddItemToCart): OrderInterface $decoratedHandler
     */
    public function __construct(
        private readonly mixed $decoratedHandler,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductVariantRepositoryInterface $productVariantRepository,
        private readonly OrderModifierInterface $orderModifier,
        private readonly CartItemFactoryInterface $cartItemFactory,
        private readonly OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private readonly GiftCardFactoryInterface $giftCardFactory,
        private readonly EntityManagerInterface $giftCardManager,
        private readonly ?UserContextInterface $userContext = null,
    ) {
        Assert::isCallable($decoratedHandler);
    }

    public function __invoke(SyliusAddItemToCart $addItemToCart): OrderInterface
    {
        /** @var ProductVariantInterface|null $productVariant */
        $productVariant = $this->productVariantRepository->findOneBy(['code' => $addItemToCart->productVariantCode]);
        $product = $productVariant?->getProduct();

        if (!$addItemToCart instanceof SetonoSyliusGiftCardAddItemToCart || !$product instanceof ProductInterface || !$product->isGiftCard()) {
            return ($this->decoratedHandler)($addItemToCart);
        }

        if ($productVariant === null) {
            throw new ProductVariantUnprocessableException();
        }

        /** @var OrderInterface|null $cart */
        $cart = $this->orderRepository->findCartByTokenValue($addItemToCart->orderTokenValue);
        if ($cart === null) {
            throw new UnprocessableCartException();
        }

        $this->assertCartAccessible($cart);

        /** @var OrderItemInterface $cartItem */
        $cartItem = $this->cartItemFactory->createNew();
        $cartItem->setVariant($productVariant);

        if ($product->isGiftCardAmountConfigurable()) {
            $giftCardAmount = $addItemToCart->getAmount();
            Assert::notNull($giftCardAmount);

            $cartItem->setUnitPrice($giftCardAmount);
            $cartItem->setImmutable(true);
        }

        $this->orderItemQuantityModifier->modify($cartItem, $addItemToCart->quantity);
        $this->orderModifier->addToOrder($cart, $cartItem);

        /** @var OrderItemUnitInterface $unit */
        foreach ($cartItem->getUnits() as $unit) {
            $giftCard = $this->giftCardFactory->createFromOrderItemUnitAndCart($unit, $cart);
            $giftCard->setCustomMessage($addItemToCart->getCustomMessage());

            // As the common flow for any add to cart action will flush later. Do not flush here.
            $this->giftCardManager->persist($giftCard);
        }

        return $cart;
    }

    /**
     * Same check as the Sylius handler: a cart of a registered customer is only reachable by that customer
     */
    private function assertCartAccessible(OrderInterface $cart): void
    {
        if (null === $this->userContext || $cart->isCreatedByGuest()) {
            return;
        }

        $cartCustomer = $cart->getCustomer();
        if (null === $cartCustomer || null === $cartCustomer->getUser()) {
            return;
        }

        $currentUser = $this->userContext->getUser();
        if ($currentUser instanceof ShopUserInterface && $currentUser->getCustomer()?->getId() === $cartCustomer->getId()) {
            return;
        }

        throw new NotFoundHttpException('Cart not found.');
    }
}
