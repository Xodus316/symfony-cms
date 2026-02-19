<?php

namespace App\Controller\Api\V1;

use App\Security\Voter\ArticleVoter;
use App\Service\ArticleService;
use App\Service\ArticleTechProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/articles/{articleId}/techproducts', name: 'api_v1_article_techproduct_')]
class ArticleTechProductController extends AbstractController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ArticleTechProductService $articleTechProductService
    ) {
    }

    /**
     * Get all tech products associated with an article
     */
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function list(int $articleId): JsonResponse
    {
        $article = $this->articleService->getArticleById($articleId);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::VIEW, $article);

        $techProducts = $this->articleTechProductService->getTechProductsForArticle($article);

        return $this->json([
            'data' => array_values($techProducts),
            'meta' => [
                'article_id' => $articleId,
                'count' => count($techProducts),
            ],
        ], Response::HTTP_OK, [], ['groups' => ['techproduct:read']]);
    }

    /**
     * Associate a tech product with an article
     */
    #[Route('', name: 'associate', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function associate(int $articleId, Request $request): JsonResponse
    {
        $article = $this->articleService->getArticleById($articleId);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);

        $data = json_decode($request->getContent(), true);
        $techProductId = $data['techProductId'] ?? null;
        $sortOrder = $data['sortOrder'] ?? 0;

        if (!$techProductId) {
            return $this->json(['error' => 'techProductId is required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $association = $this->articleTechProductService->associateTechProduct(
                $article,
                $techProductId,
                $sortOrder
            );

            return $this->json([
                'message' => 'Tech product associated successfully',
                'association' => [
                    'article_id' => $article->getId(),
                    'techproduct_id' => $techProductId,
                    'sort_order' => $sortOrder,
                ],
            ], Response::HTTP_CREATED);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Remove association between article and tech product
     */
    #[Route('/{techProductId}', name: 'dissociate', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITOR')]
    public function dissociate(int $articleId, string $techProductId): JsonResponse
    {
        $article = $this->articleService->getArticleById($articleId);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);

        $this->articleTechProductService->dissociateTechProduct($article, $techProductId);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
