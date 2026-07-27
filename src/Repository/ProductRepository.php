<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public const ITEMS_PER_PAGE = 50;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findPaginated(int $page, ?string $search = null, int $perPage = self::ITEMS_PER_PAGE): array
    {
        $page = max(1, $page);

        $qb = $this->createSearchQueryBuilder($search)
            ->orderBy('p.id', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        return $qb->getQuery()->getResult();
    }

    public function countAll(?string $search = null): int
    {
        return (int) $this->createSearchQueryBuilder($search)
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<int>
     */
    public function findIdsWithPrestashopId(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.id')
            ->where('p.prestashopId IS NOT NULL')
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $result);
    }

    public function sumTotalValue(?string $search = null): float
    {
        if (null === $search || '' === $search) {
            $result = $this->getEntityManager()->getConnection()->fetchOne(
                'SELECT COALESCE(SUM(CAST(value AS DECIMAL(12,2))), 0) FROM product'
            );

            return (float) $result;
        }

        return (float) $this->sumFilteredValue($search);
    }

    private function sumFilteredValue(string $search): float
    {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $sql = 'SELECT COALESCE(SUM(CAST(value AS DECIMAL(12,2))), 0) FROM product WHERE kod LIKE ? OR nazwa_produktu LIKE ?';
        $params = [$like, $like];

        if (ctype_digit($search)) {
            $sql .= ' OR id = ? OR prestashop_id = ?';
            $id = (int) $search;
            $params[] = $id;
            $params[] = $id;
        }

        return (float) $this->getEntityManager()->getConnection()->fetchOne($sql, $params);
    }

    private function createSearchQueryBuilder(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if (null !== $search && '' !== $search) {
            $this->applySearch($qb, $search);
        }

        return $qb;
    }

    private function applySearch(QueryBuilder $qb, string $search): void
    {
        $like = '%' . addcslashes($search, '%_\\') . '%';
        $conditions = [
            $qb->expr()->like('p.kod', ':search'),
            $qb->expr()->like('p.nazwaProduktu', ':search'),
        ];

        if (ctype_digit($search)) {
            $conditions[] = $qb->expr()->eq('p.id', ':searchId');
            $conditions[] = $qb->expr()->eq('p.prestashopId', ':searchId');
            $qb->setParameter('searchId', (int) $search);
        }

        $qb->andWhere($qb->expr()->orX(...$conditions))
            ->setParameter('search', $like);
    }

    //    /**
    //     * @return Product[] Returns an array of Product objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Product
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
