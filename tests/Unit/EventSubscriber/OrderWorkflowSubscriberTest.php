<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\EventSubscriber\OrderWorkflowSubscriber;
use Setono\SyliusGiftCardPlugin\Model\OrderInterface;
use Setono\SyliusGiftCardPlugin\Modifier\OrderGiftCardAmountModifierInterface;
use Setono\SyliusGiftCardPlugin\Operator\OrderGiftCardOperatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Event\CompletedEvent;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;

final class OrderWorkflowSubscriberTest extends TestCase
{
    #[Test]
    public function it_decrements_the_gift_card_amount_when_an_order_is_created(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $modifier = $this->createMock(OrderGiftCardAmountModifierInterface::class);
        $modifier->expects(self::once())->method('decrement')->with($order);
        $modifier->expects(self::never())->method('increment');

        $this->dispatch('workflow.sylius_order.completed.create', $order, $modifier, $this->createMock(OrderGiftCardOperatorInterface::class));
    }

    #[Test]
    public function it_restores_the_amount_and_disables_the_gift_cards_when_an_order_is_cancelled(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $modifier = $this->createMock(OrderGiftCardAmountModifierInterface::class);
        $modifier->expects(self::once())->method('increment')->with($order);
        $operator = $this->createMock(OrderGiftCardOperatorInterface::class);
        $operator->expects(self::once())->method('disable')->with($order);

        $this->dispatch('workflow.sylius_order.completed.cancel', $order, $modifier, $operator);
    }

    #[Test]
    public function it_associates_the_gift_cards_to_the_customer_when_the_checkout_completes(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $operator = $this->createMock(OrderGiftCardOperatorInterface::class);
        $operator->expects(self::once())->method('associateToCustomer')->with($order);

        $this->dispatch('workflow.sylius_order_checkout.completed.complete', $order, $this->createMock(OrderGiftCardAmountModifierInterface::class), $operator);
    }

    #[Test]
    public function it_enables_and_sends_the_gift_cards_when_an_order_is_paid(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $calls = [];
        $operator = $this->createMock(OrderGiftCardOperatorInterface::class);
        $operator->expects(self::once())->method('enable')->with($order)->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'enable';
        });
        $operator->expects(self::once())->method('send')->with($order)->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'send';
        });

        $this->dispatch('workflow.sylius_order_payment.completed.pay', $order, $this->createMock(OrderGiftCardAmountModifierInterface::class), $operator);

        self::assertSame(['enable', 'send'], $calls);
    }

    private function dispatch(
        string $eventName,
        OrderInterface $order,
        OrderGiftCardAmountModifierInterface $modifier,
        OrderGiftCardOperatorInterface $operator,
    ): void {
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new OrderWorkflowSubscriber($modifier, $operator));
        $dispatcher->dispatch(new CompletedEvent($order, new Marking(), new Transition('transition', 'from', 'to')), $eventName);
    }
}
