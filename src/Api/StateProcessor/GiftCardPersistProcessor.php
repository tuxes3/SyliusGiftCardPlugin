<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Setono\SyliusGiftCardPlugin\Model\GiftCardInterface;

/**
 * Marks gift cards created through the API as such before they are persisted
 *
 * @implements ProcessorInterface<GiftCardInterface, GiftCardInterface>
 */
final class GiftCardPersistProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<GiftCardInterface, GiftCardInterface> $persistProcessor
     */
    public function __construct(private readonly ProcessorInterface $persistProcessor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof GiftCardInterface) {
            $data->setOrigin(GiftCardInterface::ORIGIN_API);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
