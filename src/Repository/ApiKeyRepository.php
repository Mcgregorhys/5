<?php

namespace App\Repository;

use App\Entity\ApiKey;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiKey>
 */
class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findActiveByTokenHash(string $tokenHash): ?ApiKey
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.tokenHash = :hash')
            ->andWhere('k.isActive = true')
            ->setParameter('hash', $tokenHash)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<ApiKey>
     */
    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('k')
            ->andWhere('k.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('k.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
