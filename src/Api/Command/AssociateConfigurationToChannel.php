<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Api\Command;

use Setono\SyliusGiftCardPlugin\Api\Attribute\GiftCardConfigurationCodeAware;

#[GiftCardConfigurationCodeAware]
class AssociateConfigurationToChannel implements ConfigurationCodeAwareInterface
{
    public function __construct(public string $localeCode, public string $channelCode, public ?string $configurationCode = null)
    {
    }

    public function getConfigurationCode(): ?string
    {
        return $this->configurationCode;
    }

    public function setConfigurationCode(?string $configurationCode): void
    {
        $this->configurationCode = $configurationCode;
    }
}
