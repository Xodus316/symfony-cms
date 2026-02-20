<?php

namespace App\Controller\Api\V1;

use App\DTO\CreateArticleDTO;
use App\DTO\UpdateArticleDTO;
use App\Entity\Article;
use App\Security\Voter\ArticleVoter;
use App\Service\ArticleService;
use App\Service\ArticleTechProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/v1/articles', name: 'api_v1_article_')]
class ArticleController extends AbstractController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ArticleTechProductService $articleTechProductService,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * List all articles with pagination
     */
    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        $articles = $this->articleService->getAllArticles($page, $limit);
        $total = $this->articleService->getTotalCount();

        return $this->json([
            'data' => $articles,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ], Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    /**
     * Get a single article by ID
     */
    #[Route('/{id}', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function get(string $id): JsonResponse
    {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::VIEW, $article);

        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    /**
     * Get a single article by slug
     */
    #[Route('/slug/{slug}', name: 'get_by_slug', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function getBySlug(string $slug): JsonResponse
    {
        $article = $this->articleService->getArticleBySlug($slug);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::VIEW, $article);

        return $this->json($article, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    /**
     * Create a new article
     */
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function create(
        #[MapRequestPayload] CreateArticleDTO $dto
    ): JsonResponse {
        $user = $this->getUser();
        $article = $this->articleService->createArticle($dto, $user);

        return $this->json($article, Response::HTTP_CREATED, [], ['groups' => ['article:read']]);
    }

    /**
     * Update an existing article
     */
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_EDITOR')]
    public function update(
        string $id,
        #[MapRequestPayload] UpdateArticleDTO $dto
    ): JsonResponse {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);

        $updatedArticle = $this->articleService->updateArticle($article, $dto);

        return $this->json($updatedArticle, Response::HTTP_OK, [], ['groups' => ['article:read']]);
    }

    /**
     * Get article with embedded tech products (hybrid MySQL + MongoDB query)
     */
    #[Route('/{id}/with-products', name: 'get_with_products', methods: ['GET'])]
    #[IsGranted('ROLE_VIEWER')]
    public function getWithProducts(string $id): JsonResponse
    {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::VIEW, $article);

        $data = $this->articleTechProductService->getArticleWithProducts($article);

        return $this->json([
            'article' => $data['article'],
            'techProducts' => array_values($data['techProducts']),
        ], Response::HTTP_OK, [], [
            'groups' => ['article:read', 'techproduct:read']
        ]);
    }

    /**
     * Delete an article
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id): JsonResponse
    {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $this->denyAccessUnlessGranted(ArticleVoter::DELETE, $article);

        $this->articleService->deleteArticle($article);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
