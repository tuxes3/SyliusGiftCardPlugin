<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Modifier;

use Doctrine\Common\Persistence\ObjectManager;
use RuntimeException;
use Safe\Exceptions\StringsException;
use function Safe\sprintf;
use Setono\SyliusGiftCardPlugin\Model\AdjustmentInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCardInterface;
use Setono\SyliusGiftCardPlugin\Model\OrderInterface;

/**
 * This class' responsibility is to modify the amount on a gift card when it's used to pay for an order
 */
final class OrderGiftCardAmountModifier implements OrderGiftCardAmountModifierInterface
{
    /** @var ObjectManager */
    private $giftCardManager;

    public function __construct(ObjectManager $giftCardManager)
    {
        $this->giftCardManager = $giftCardManager;
    }

    /**
     * @throws StringsException
     */
    public function decrement(OrderInterface $order): void
    {
        foreach ($order->getAdjustments(AdjustmentInterface::ORDER_GIFT_CARD_ADJUSTMENT) as $adjustment) {
            $giftCard = self::getGiftCard($order, $adjustment->getOriginCode());

            $amount = abs($adjustment->getAmount());

            if ($amount >= $giftCard->getAmount()) {
                $giftCard->disable();
                $giftCard->setAmount(0);
            }

            if ($amount < $giftCard->getAmount()) {
                $giftCard->enable();

                $giftCard->setAmount($giftCard->getAmount() - $amount);
            }
        }

        $this->giftCardManager->flush();
    }

    /**
     * @throws StringsException
     */
    public function increment(OrderInterface $order): void
    {
        foreach ($order->getAdjustments(AdjustmentInterface::ORDER_GIFT_CARD_ADJUSTMENT) as $adjustment) {
            $giftCard = self::getGiftCard($order, $adjustment->getOriginCode());

            $giftCard->setAmount($giftCard->getAmount() + abs($adjustment->getAmount()));

            if ($giftCard->getAmount() > 0) {
                $giftCard->enable();
            }
        }

        $this->giftCardManager->flush();
    }

    /**
     * @throws StringsException
     */
    private static function getGiftCard(OrderInterface $order, string $code): GiftCardInterface
    {
        foreach ($order->getGiftCards() as $giftCard) {
            if ($giftCard->getCode() === $code) {
                return $giftCard;
            }
        }

        throw new RuntimeException(sprintf('The order %s does not have a gift card with code %s', $order->getNumber(), $code));
    }
}
