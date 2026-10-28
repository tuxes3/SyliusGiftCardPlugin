<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\Attribute;

/**
 * Marks a command that receives the code of the gift card configuration in the URI as a constructor argument
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class GiftCardConfigurationCodeAware
{
    public const DEFAULT_ARGUMENT_NAME = 'configurationCode';

    public function __construct(public string $constructorArgumentName = self::DEFAULT_ARGUMENT_NAME)
    {
    }
}
