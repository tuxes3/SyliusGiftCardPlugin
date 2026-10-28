<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Controller\Action;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Applicator\GiftCardApplicatorInterface;
use Setono\SyliusGiftCardPlugin\Controller\Action\AddGiftCardToOrderAction;
use Setono\SyliusGiftCardPlugin\Controller\Action\AddGiftCardToOrderCommand;
use Setono\SyliusGiftCardPlugin\Model\GiftCardInterface;
use Setono\SyliusGiftCardPlugin\Model\OrderInterface;
use Setono\SyliusGiftCardPlugin\Resolver\RedirectUrlResolverInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

final class AddGiftCardToOrderActionTest extends TestCase
{
    #[Test]
    public function it_renders_the_empty_form_with_status_200(): void
    {
        $response = $this->invoke(submitted: false, valid: false);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('form', $response->getContent());
    }

    #[Test]
    public function it_renders_an_invalid_form_with_status_400(): void
    {
        $response = $this->invoke(submitted: true, valid: false);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('form', $response->getContent());
    }

    #[Test]
    public function it_redirects_after_applying_a_valid_gift_card(): void
    {
        $response = $this->invoke(submitted: true, valid: true);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/cart', $response->getTargetUrl());
    }

    private function invoke(bool $submitted, bool $valid): Response
    {
        $order = $this->createMock(OrderInterface::class);

        $cartContext = $this->createMock(CartContextInterface::class);
        $cartContext->method('getCart')->willReturn($order);

        $form = $this->createMock(FormInterface::class);
        $form->method('isSubmitted')->willReturn($submitted);
        $form->method('isValid')->willReturn($valid);
        $form->method('createView')->willReturn(new FormView());

        $giftCard = $this->createMock(GiftCardInterface::class);

        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->method('create')->willReturnCallback(
            static function (string $type, AddGiftCardToOrderCommand $command) use ($form, $valid, $giftCard): FormInterface {
                if ($valid) {
                    $command->setGiftCard($giftCard);
                }

                return $form;
            },
        );

        $twig = $this->createMock(Environment::class);
        $twig->method('render')->willReturn('form');

        $redirectUrlResolver = $this->createMock(RedirectUrlResolverInterface::class);
        $redirectUrlResolver->method('getUrlToRedirectTo')->willReturn('/cart');

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($this->createMock(EntityManagerInterface::class));

        $action = new AddGiftCardToOrderAction(
            $formFactory,
            $cartContext,
            $this->createMock(GiftCardApplicatorInterface::class),
            $redirectUrlResolver,
            $twig,
            $managerRegistry,
        );

        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));

        return $action($request);
    }
}
