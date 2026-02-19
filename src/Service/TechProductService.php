<?php

namespace App\Service;

use App\Document\TechProduct;
use App\DTO\CreateTechProductDTO;
use App\DTO\UpdateTechProductDTO;
use App\Repository\TechProductRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

class TechProductService
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly TechProductRepository $techProductRepository
    ) {
    }

    /**
     * Create a new tech product
     */
    public function createTechProduct(CreateTechProductDTO $dto): TechProduct
    {
        $techProduct = new TechProduct();
        $techProduct->setProductId($dto->productId);
        $techProduct->setName($dto->name);
        $techProduct->setSpecs($dto->specs);

        if ($dto->pricing !== null) {
            $techProduct->setPricing($dto->pricing);
        }

        $this->documentManager->persist($techProduct);
        $this->documentManager->flush();

        return $techProduct;
    }

    /**
     * Update an existing tech product
     */
    public function updateTechProduct(TechProduct $techProduct, UpdateTechProductDTO $dto): TechProduct
    {
        if ($dto->productId !== null) {
            $techProduct->setProductId($dto->productId);
        }

        if ($dto->name !== null) {
            $techProduct->setName($dto->name);
        }

        if ($dto->specs !== null) {
            $techProduct->setSpecs($dto->specs);
        }

        if ($dto->pricing !== null) {
            $techProduct->setPricing($dto->pricing);
        }

        $this->documentManager->flush();

        return $techProduct;
    }

    /**
     * Delete a tech product
     */
    public function deleteTechProduct(TechProduct $techProduct): void
    {
        $this->documentManager->remove($techProduct);
        $this->documentManager->flush();
    }

    /**
     * Get tech product by MongoDB ID
     */
    public function getTechProductById(string $id): ?TechProduct
    {
        return $this->techProductRepository->find($id);
    }

    /**
     * Get tech product by product ID
     */
    public function getTechProductByProductId(string $productId): ?TechProduct
    {
        return $this->techProductRepository->findOneByProductId($productId);
    }

    /**
     * Get all tech products with pagination
     */
    public function getAllTechProducts(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        return $this->techProductRepository->createQueryBuilder()
            ->skip($offset)
            ->limit($limit)
            ->sort('createdAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * Get tech products by multiple MongoDB IDs (batch fetch)
     *
     * @param array<string> $ids
     * @return array<TechProduct>
     */
    public function getTechProductsByIds(array $ids): array
    {
        return $this->techProductRepository->findByIds($ids);
    }

    /**
     * Search tech products by name or specs
     */
    public function searchTechProducts(string $query, int $limit = 20): array
    {
        return $this->techProductRepository->search($query, $limit);
    }

    /**
     * Get total count of tech products
     */
    public function getTotalCount(): int
    {
        return $this->techProductRepository->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();
    }
}
