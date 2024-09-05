<?php

namespace CardPrinterService\Repository;

use CardPrinterService\Entity\Template;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Template>
 *
 * @method Template|null find($id, $lockMode = null, $lockVersion = null)
 * @method Template|null findOneBy(array $criteria, array $orderBy = null)
 * @method Template[]    findAll()
 * @method Template[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Template::class);
    }

    public function save(Template $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Template $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveTemplateByParams(string $customer, string $type, string $product, ?string $excludedId): mixed
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.customer = :customer')
            ->andWhere('t.type = :type')
            ->andWhere('t.product = :product')
            ->andWhere('t.isEnabled = :isEnabled')
            ->setParameter('customer', $customer)
            ->setParameter('type', $type)
            ->setParameter('product', $product)
            ->setParameter('isEnabled', true);

        if ($excludedId) {
            $qb->andWhere('t.id <> :excludedId')->setParameter('excludedId', $excludedId);
        }

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function disableTemplates(string $type, string $product): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.isEnabled', ':disabled')
            ->where('t.type = :type')
            ->andWhere('t.product = :product')
            ->andWhere('t.isEnabled = :isEnabled')
            ->setParameters([
                'type' => $type,
                'product' => $product,
                'isEnabled' => true,
                'disabled' => false,
            ])
            ->getQuery()
            ->execute();
    }
}
