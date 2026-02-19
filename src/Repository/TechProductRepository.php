<?php

namespace App\Repository;

use App\Document\TechProduct;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<TechProduct>
 */
class TechProductRepository extends DocumentRepository
{
    public function __construct(DocumentManager $dm)
    {
        $uow = $dm->getUnitOfWork();
        $classMetadata = $dm->getClassMetadata(TechProduct::class);

        parent::__construct($dm, $uow, $classMetadata);
    }

    /**
     * Find a product by its product ID
     */
    public function findOneByProductId(string $productId): ?TechProduct
    {
        return $this->findOneBy(['productId' => $productId]);
    }

    /**
     * Find products by multiple MongoDB IDs (batch fetch)
     *
     * @param array<string> $ids
     * @return array<TechProduct>
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder()
            ->field('id')->in($ids)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Search products by name or specs
     */
    public function search(string $query, int $limit = 20): array
    {
        return $this->createQueryBuilder()
            ->text($query)
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }
}
