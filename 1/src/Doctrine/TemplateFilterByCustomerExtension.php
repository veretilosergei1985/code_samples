<?php

namespace CardPrinterService\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use CardPrinterService\Entity\Template;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use Ubitransport\KeycloakSecurityBundle\Security\UserDto;

class TemplateFilterByCustomerExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(protected readonly Security $security)
    {
    }

    public function applyToItem(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, array $identifiers, Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== Template::class) {
            return;
        }

        $this->applyCustomerShortnameCriteria($queryBuilder);
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, Operation $operation = null, array $context = []): void
    {
        if ($resourceClass !== Template::class) {
            return;
        }

        $this->applyCustomerShortnameCriteria($queryBuilder);
    }

    private function applyCustomerShortnameCriteria(QueryBuilder $queryBuilder): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof UserDto) {
            throw new \Exception('The user is not authorized to retrieve this collection', 401);
        }

        $customerShortname = $user->getCustomer();

        $rootAlias = $queryBuilder->getRootAliases()[0];
        $queryBuilder->andWhere(sprintf('%s.customer = :customerShortname', $rootAlias))
            ->setParameter('customerShortname', $customerShortname);
    }
}
