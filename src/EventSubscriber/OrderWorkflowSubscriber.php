<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\EventSubscriber;

use Setono\SyliusGiftCardPlugin\Model\OrderInterface;
use Setono\SyliusGiftCardPlugin\Modifier\OrderGiftCardAmountModifierInterface;
use Setono\SyliusGiftCardPlugin\Operator\OrderGiftCardOperatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Webmozart\Assert\Assert;

/**
 * Runs the same actions as the winzou callbacks in config/state_machine for applications
 * using the Symfony Workflow adapter, which is the default since Sylius 2.
 * Each adapter only dispatches its own events, so the actions never run twice.
 */
final class OrderWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly OrderGiftCardAmountModifierInterface $orderGiftCardAmountModifier,
        private readonly OrderGiftCardOperatorInterface $orderGiftCardOperator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.sylius_order.completed.create' => 'decrementGiftCardAmount',
            'workflow.sylius_order.completed.cancel' => [
                ['incrementGiftCardAmount', 10],
                ['disableGiftCards', 0],
            ],
            'workflow.sylius_order_checkout.completed.complete' => 'associateGiftCardsToCustomer',
            'workflow.sylius_order_payment.completed.pay' => [
                ['enableGiftCards', 10],
                ['sendGiftCards', 0],
            ],
        ];
    }

    public function decrementGiftCardAmount(CompletedEvent $event): void
    {
        $this->orderGiftCardAmountModifier->decrement(self::getOrder($event));
    }

    public function incrementGiftCardAmount(CompletedEvent $event): void
    {
        $this->orderGiftCardAmountModifier->increment(self::getOrder($event));
    }

    public function disableGiftCards(CompletedEvent $event): void
    {
        $this->orderGiftCardOperator->disable(self::getOrder($event));
    }

    public function associateGiftCardsToCustomer(CompletedEvent $event): void
    {
        $this->orderGiftCardOperator->associateToCustomer(self::getOrder($event));
    }

    public function enableGiftCards(CompletedEvent $event): void
    {
        $this->orderGiftCardOperator->enable(self::getOrder($event));
    }

    public function sendGiftCards(CompletedEvent $event): void
    {
        $this->orderGiftCardOperator->send(self::getOrder($event));
    }

    private static function getOrder(CompletedEvent $event): OrderInterface
    {
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        return $order;
    }
}
