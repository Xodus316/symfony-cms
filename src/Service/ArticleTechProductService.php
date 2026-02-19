<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleTechProduct;
use App\Repository\ArticleTechProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ArticleTechProductService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleTechProductRepository $articleTechProductRepository,
        private readonly TechProductService $techProductService
    ) {
    }

    /**
     * Associate a tech product with an article
     */
    public function associateTechProduct(
        Article $article,
        string $techProductMongoId,
        int $sortOrder = 0
    ): ArticleTechProduct {
        // Check if association already exists
        if ($this->articleTechProductRepository->exists($article, $techProductMongoId)) {
            throw new \RuntimeException('This product is already associated with the article');
        }

        // Verify the tech product exists in MongoDB
        $techProduct = $this->techProductService->getTechProductById($techProductMongoId);
        if (!$techProduct) {
            throw new \RuntimeException('Tech product not found');
        }

        $association = new ArticleTechProduct();
        $association->setArticle($article);
        $association->setTechproductMongoId($techProductMongoId);
        $association->setSortOrder($sortOrder);

        $this->entityManager->persist($association);
        $this->entityManager->flush();

        return $association;
    }

    /**
     * Dissociate a tech product from an article
     */
    public function dissociateTechProduct(Article $article, string $techProductMongoId): void
    {
        $this->articleTechProductRepository->deleteAssociation($article, $techProductMongoId);
    }

    /**
     * Get all tech products for an article
     * This performs a hybrid query: MySQL junction table + MongoDB batch fetch
     *
     * @return array<\App\Document\TechProduct>
     */
    public function getTechProductsForArticle(Article $article): array
    {
        // Step 1: Get MongoDB IDs from MySQL junction table
        $mongoIds = $this->articleTechProductRepository->getTechProductIdsForArticle($article);

        if (empty($mongoIds)) {
            return [];
        }

        // Step 2: Batch fetch products from MongoDB using $in operator
        return $this->techProductService->getTechProductsByIds($mongoIds);
    }

    /**
     * Get article with embedded tech products (hybrid query)
     */
    public function getArticleWithProducts(Article $article): array
    {
        $techProducts = $this->getTechProductsForArticle($article);

        return [
            'article' => $article,
            'techProducts' => $techProducts,
        ];
    }

    /**
     * Get tech products for multiple articles (optimized batch query)
     *
     * @param array<Article> $articles
     * @return array<int, array<\App\Document\TechProduct>> Article ID => TechProducts
     */
    public function getTechProductsForArticles(array $articles): array
    {
        // Step 1: Get all associations grouped by article ID
        $associationsByArticle = $this->articleTechProductRepository->getTechProductIdsForArticles($articles);

        if (empty($associationsByArticle)) {
            return [];
        }

        // Step 2: Collect all unique MongoDB IDs
        $allMongoIds = [];
        foreach ($associationsByArticle as $mongoIds) {
            $allMongoIds = array_merge($allMongoIds, $mongoIds);
        }
        $allMongoIds = array_unique($allMongoIds);

        // Step 3: Batch fetch all products from MongoDB
        $techProducts = $this->techProductService->getTechProductsByIds($allMongoIds);

        // Step 4: Index products by MongoDB ID for quick lookup
        $productsById = [];
        foreach ($techProducts as $product) {
            $productsById[$product->getId()] = $product;
        }

        // Step 5: Map products back to articles
        $result = [];
        foreach ($associationsByArticle as $articleId => $mongoIds) {
            $result[$articleId] = [];
            foreach ($mongoIds as $mongoId) {
                if (isset($productsById[$mongoId])) {
                    $result[$articleId][] = $productsById[$mongoId];
                }
            }
        }

        return $result;
    }

    /**
     * Update sort order for a product association
     */
    public function updateSortOrder(Article $article, string $techProductMongoId, int $sortOrder): void
    {
        $association = $this->entityManager->getRepository(ArticleTechProduct::class)->findOneBy([
            'article' => $article,
            'techproductMongoId' => $techProductMongoId,
        ]);

        if (!$association) {
            throw new \RuntimeException('Association not found');
        }

        $association->setSortOrder($sortOrder);
        $this->entityManager->flush();
    }
}
