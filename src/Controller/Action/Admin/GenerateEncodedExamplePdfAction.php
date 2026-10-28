<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Controller\Action\Admin;

use Setono\SyliusGiftCardPlugin\Factory\GiftCardFactoryInterface;
use Setono\SyliusGiftCardPlugin\Form\Type\GiftCardConfigurationType;
use Setono\SyliusGiftCardPlugin\Model\GiftCardConfigurationInterface;
use Setono\SyliusGiftCardPlugin\Renderer\PdfRendererInterface;
use Setono\SyliusGiftCardPlugin\Repository\GiftCardConfigurationRepositoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

final class GenerateEncodedExamplePdfAction
{
    public function __construct(private readonly GiftCardFactoryInterface $giftCardFactory, private readonly GiftCardConfigurationRepositoryInterface $giftCardConfigurationRepository, private readonly PdfRendererInterface $pdfRenderer, private readonly FormFactoryInterface $formFactory)
    {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $giftCard = $this->giftCardFactory->createExample();

        /** @var GiftCardConfigurationInterface|null $giftCardConfiguration */
        $giftCardConfiguration = $this->giftCardConfigurationRepository->find($id);
        Assert::isInstanceOf($giftCardConfiguration, GiftCardConfigurationInterface::class);

        // The edit form sends its data as PUT, so the form must expect PUT to pick the changes up
        $form = $this->formFactory->create(GiftCardConfigurationType::class, $giftCardConfiguration, ['method' => Request::METHOD_PUT]);
        $form->handleRequest($request);

        return new Response($this->pdfRenderer->render($giftCard, $giftCardConfiguration)->getEncodedContent());
    }
}
