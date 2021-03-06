<?php

declare(strict_types=1);

namespace spec\Setono\SyliusGiftCardPlugin\EmailManager;

use PhpSpec\ObjectBehavior;
use Setono\SyliusGiftCardPlugin\EmailManager\GiftCardOrderEmailManager;
use Setono\SyliusGiftCardPlugin\EmailManager\GiftCardOrderEmailManagerInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCardInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Mailer\Sender\SenderInterface;

final class GiftCardOrderEmailManagerSpec extends ObjectBehavior
{
    public function let(SenderInterface $sender): void
    {
        $this->beConstructedWith($sender);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(GiftCardOrderEmailManager::class);
    }

    public function it_implements_gift_card_order_email_manager_interface(): void
    {
        $this->shouldHaveType(GiftCardOrderEmailManagerInterface::class);
    }

    public function it_sends_email_with_gift_card_codes(
        OrderInterface $order,
        CustomerInterface $customer,
        GiftCardInterface $giftCard,
        SenderInterface $sender
    ): void {
        $customer->getEmail()->willReturn('example@shop.com');
        $order->getCustomer()->willReturn($customer);

        $sender->send(
            GiftCardOrderEmailManagerInterface::EMAIL_CONFIG_NAME,
            ['example@shop.com'],
            ['giftCards' => [$giftCard], 'order' => $order]
        )->shouldBeCalled();

        $this->sendEmailWithGiftCards($order, [$giftCard]);
    }
}
