<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\ArticleTechProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleTechProduct>
 */
class ArticleTechProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleTechProduct::class);
    }

    /**
     * Get all TechProduct MongoDB IDs for an article
     *
     * @return array<string>
     */
    public function getTechProductIdsForArticle(Article $article): array
    {
        $results = $this->createQueryBuilder('atp')
            ->select('atp.techproductMongoId')
            ->andWhere('atp.article = :article')
            ->setParameter('article', $article)
            ->orderBy('atp.sortOrder', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($results, 'techproductMongoId');
    }

    /**
     * Get all TechProduct MongoDB IDs for multiple articles
     *
     * @param array<Article> $articles
     * @return array<int, array<string>> Article ID => TechProduct MongoDB IDs
     */
    public function getTechProductIdsForArticles(array $articles): array
    {
        if (empty($articles)) {
            return [];
        }

        $results = $this->createQueryBuilder('atp')
            ->select('IDENTITY(atp.article) as articleId', 'atp.techproductMongoId')
            ->andWhere('atp.article IN (:articles)')
            ->setParameter('articles', $articles)
            ->orderBy('atp.sortOrder', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $grouped = [];
        foreach ($results as $result) {
            $articleId = $result['articleId'];
            if (!isset($grouped[$articleId])) {
                $grouped[$articleId] = [];
            }
            $grouped[$articleId][] = $result['techproductMongoId'];
        }

        return $grouped;
    }

    /**
     * Check if an article-techproduct association exists
     */
    public function exists(Article $article, string $techproductMongoId): bool
    {
        $count = $this->createQueryBuilder('atp')
            ->select('COUNT(atp.id)')
            ->andWhere('atp.article = :article')
            ->andWhere('atp.techproductMongoId = :mongoId')
            ->setParameter('article', $article)
            ->setParameter('mongoId', $techproductMongoId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Delete association between article and techproduct
     */
    public function deleteAssociation(Article $article, string $techproductMongoId): void
    {
        $this->createQueryBuilder('atp')
            ->delete()
            ->andWhere('atp.article = :article')
            ->andWhere('atp.techproductMongoId = :mongoId')
            ->setParameter('article', $article)
            ->setParameter('mongoId', $techproductMongoId)
            ->getQuery()
            ->execute();
    }
}
