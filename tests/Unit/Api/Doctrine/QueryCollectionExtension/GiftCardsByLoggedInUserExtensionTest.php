<?php

declare(strict_types=1);

namespace Setono\SyliusGiftCardPlugin\Tests\Unit\Api\Doctrine\QueryCollectionExtension;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Setono\SyliusGiftCardPlugin\Api\Doctrine\QueryCollectionExtension\GiftCardsByLoggedInUserExtension;
use Setono\SyliusGiftCardPlugin\Model\GiftCardInterface;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class GiftCardsByLoggedInUserExtensionTest extends TestCase
{
    #[Test]
    public function it_limits_the_shop_gift_card_collection_to_the_logged_in_customer(): void
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn(42);
        $shopUser = new ShopUser();
        $shopUser->setCustomer($customer);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getRootAliases')->willReturn(['root_alias']);
        $queryBuilder->expects(self::once())->method('andWhere')->with('root_alias.customer = :customer')->willReturnSelf();
        $queryBuilder->expects(self::once())->method('setParameter')->with('customer', 42, Types::INTEGER)->willReturnSelf();

        $this->createExtension(new ShopApiSection(), $shopUser)->applyToCollection($queryBuilder, $this->createMock(QueryNameGeneratorInterface::class), GiftCardInterface::class);
    }

    #[Test]
    public function it_denies_the_shop_gift_card_collection_to_visitors(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->createExtension(new ShopApiSection(), null)->applyToCollection($this->createMock(QueryBuilder::class), $this->createMock(QueryNameGeneratorInterface::class), GiftCardInterface::class);
    }

    #[Test]
    public function it_does_not_limit_the_admin_gift_card_collection(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::never())->method('andWhere');

        // Any user, e.g. an application's own API user that is no Sylius admin user
        $this->createExtension(new AdminApiSection(), null)->applyToCollection($queryBuilder, $this->createMock(QueryNameGeneratorInterface::class), GiftCardInterface::class);
    }

    #[Test]
    public function it_does_not_limit_other_collections(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::never())->method('andWhere');

        $this->createExtension(new ShopApiSection(), null)->applyToCollection($queryBuilder, $this->createMock(QueryNameGeneratorInterface::class), ShopUser::class);
    }

    private function createExtension(SectionInterface $section, ?ShopUser $user): GiftCardsByLoggedInUserExtension
    {
        $sectionProvider = $this->createMock(SectionProviderInterface::class);
        $sectionProvider->method('getSection')->willReturn($section);

        $userContext = $this->createMock(UserContextInterface::class);
        $userContext->method('getUser')->willReturn($user);

        return new GiftCardsByLoggedInUserExtension($userContext, $sectionProvider);
    }
}
