<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Form\Type;

use Setono\SyliusGiftCardPlugin\Provider\PdfOrientationProviderInterface;
use Setono\SyliusGiftCardPlugin\Provider\PdfPageSizeProviderInterface;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class GiftCardConfigurationType extends AbstractResourceType
{
    private PdfPageSizeProviderInterface $pdfFormatProvider;

    private PdfOrientationProviderInterface $pdfOrientationProvider;

    /**
     * @psalm-param array<array-key, string> $validationGroups
     */
    public function __construct(
        PdfPageSizeProviderInterface $pdfFormatProvider,
        PdfOrientationProviderInterface $pdfOrientationProvider,
        string $dataClass,
        array $validationGroups
    ) {
        parent::__construct($dataClass, $validationGroups);

        $this->pdfFormatProvider = $pdfFormatProvider;
        $this->pdfOrientationProvider = $pdfOrientationProvider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.code',
        ]);
        $builder->add('enabled', CheckboxType::class, [
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.enabled',
            'required' => false,
        ]);
        $builder->add('default', CheckboxType::class, [
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.default',
            'required' => false,
        ]);
        $builder->add('backgroundImage', GiftCardConfigurationImageType::class, [
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.background_image',
            'required' => false,
            'remove_type' => true,
        ]);
        $builder->add('channelConfigurations', CollectionType::class, [
            'required' => false,
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.channel_configurations',
            'entry_type' => GiftCardChannelConfigurationType::class,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
        ]);
        $builder->add('defaultValidityPeriod', DatePeriodType::class, [
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.default_validity_period',
        ]);
        $builder->add('pageSize', ChoiceType::class, [
            'choices' => $this->pdfFormatProvider->getAvailablePageSizes(),
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.page_size',
            'choice_translation_domain' => false,
            'choice_label' => function (string $value) {
                return $value;
            },
            'placeholder' => PdfPageSizeProviderInterface::FORMAT_A5,
            'empty_data' => PdfPageSizeProviderInterface::FORMAT_A5,
        ]);
        $builder->add('orientation', ChoiceType::class, [
            'choices' => $this->pdfOrientationProvider->getAvailableOrientations(),
            'label' => 'setono_sylius_gift_card.form.gift_card_configuration.orientation',
            'choice_translation_domain' => false,
            'choice_label' => function (string $value) {
                return $value;
            },
        ]);
        $builder->get('defaultValidityPeriod')->addModelTransformer(
            new CallbackTransformer(
                function (?string $period): array {
                    $value = null;
                    $unit = null;
                    if (null !== $period) {
                        [$value, $unit] = \explode(' ', $period);
                    }

                    return [
                        'value' => $value,
                        'unit' => $unit,
                    ];
                },
                function (array $data): ?string {
                    if (null === $data['value']) {
                        return null;
                    }

                    /** @psalm-suppress MixedArgumentTypeCoercion */
                    return \implode(' ', $data);
                }
            )
        );
    }

    public function getBlockPrefix(): string
    {
        return 'setono_sylius_gift_card_gift_card_configuration';
    }
}
