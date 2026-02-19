<?php

namespace App\Controller\Api\V1;

use App\DTO\CreateTechProductDTO;
use App\DTO\UpdateTechProductDTO;
use App\Security\Voter\TechProductVoter;
use App\Service\TechProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/techproducts', name: 'api_v1_techproduct_')]
class TechProductController extends AbstractController
{
    public function __construct(
        private readonly TechProductService $techProductService
    ) {
    }

    /**
     * List all tech products with pagination
     */
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        $techProducts = $this->techProductService->getAllTechProducts($page, $limit);
        $total = $this->techProductService->getTotalCount();

        return $this->json([
            'data' => array_values($techProducts), // Re-index array
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ], Response::HTTP_OK, [], ['groups' => ['techproduct:read']]);
    }

    /**
     * Search tech products
     */
    #[Route('/search', name: 'search', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));

        if (empty($query)) {
            return $this->json(['error' => 'Search query is required'], Response::HTTP_BAD_REQUEST);
        }

        $results = $this->techProductService->searchTechProducts($query, $limit);

        return $this->json([
            'data' => array_values($results),
            'meta' => [
                'query' => $query,
                'count' => count($results),
            ],
        ], Response::HTTP_OK, [], ['groups' => ['techproduct:read']]);
    }

    /**
     * Get a single tech product by MongoDB ID
     */
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function get(string $id): JsonResponse
    {
        $techProduct = $this->techProductService->getTechProductById($id);

        if (!$techProduct) {
            return $this->json(['error' => 'Tech product not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(TechProductVoter::VIEW, $techProduct);

        return $this->json($techProduct, Response::HTTP_OK, [], ['groups' => ['techproduct:read']]);
    }

    /**
     * Create a new tech product
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function create(
        #[MapRequestPayload] CreateTechProductDTO $dto
    ): JsonResponse {
        // Check if product ID already exists
        if ($this->techProductService->getTechProductByProductId($dto->productId)) {
            return $this->json(
                ['error' => 'Product ID already exists'],
                Response::HTTP_CONFLICT
            );
        }

        $techProduct = $this->techProductService->createTechProduct($dto);

        return $this->json($techProduct, Response::HTTP_CREATED, [], ['groups' => ['techproduct:read']]);
    }

    /**
     * Update an existing tech product
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_EDITOR')]
    public function update(
        string $id,
        #[MapRequestPayload] UpdateTechProductDTO $dto
    ): JsonResponse {
        $techProduct = $this->techProductService->getTechProductById($id);

        if (!$techProduct) {
            return $this->json(['error' => 'Tech product not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(TechProductVoter::EDIT, $techProduct);

        // Check if updating product ID conflicts with existing
        if ($dto->productId && $dto->productId !== $techProduct->getProductId()) {
            $existing = $this->techProductService->getTechProductByProductId($dto->productId);
            if ($existing) {
                return $this->json(
                    ['error' => 'Product ID already exists'],
                    Response::HTTP_CONFLICT
                );
            }
        }

        $updatedTechProduct = $this->techProductService->updateTechProduct($techProduct, $dto);

        return $this->json($updatedTechProduct, Response::HTTP_OK, [], ['groups' => ['techproduct:read']]);
    }

    /**
     * Delete a tech product
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): JsonResponse
    {
        $techProduct = $this->techProductService->getTechProductById($id);

        if (!$techProduct) {
            return $this->json(['error' => 'Tech product not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(TechProductVoter::DELETE, $techProduct);

        $this->techProductService->deleteTechProduct($techProduct);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
