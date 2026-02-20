<?php

namespace App\Controller\Admin;

use App\DTO\CreateArticleDTO;
use App\DTO\UpdateArticleDTO;
use App\Entity\Article;
use App\Security\Voter\ArticleVoter;
use App\Service\ArticleService;
use App\Service\ArticleTechProductService;
use App\Service\TechProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/articles', name: 'admin_article_')]
#[IsGranted('ROLE_VIEWER')]
class ArticleAdminController extends AbstractController
{
    public function __construct(
        private readonly ArticleService $articleService,
        private readonly ArticleTechProductService $articleTechProductService,
        private readonly TechProductService $techProductService
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $articles = $this->articleService->getAllArticles($page, $limit);
        $total = $this->articleService->getTotalCount();

        return $this->render('admin/article/list.html.twig', [
            'articles' => $articles,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/{id}', name: 'show')]
    public function show(string $id): Response
    {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        $this->denyAccessUnlessGranted(ArticleVoter::VIEW, $article);

        $techProducts = $this->articleTechProductService->getTechProductsForArticle($article);

        return $this->render('admin/article/show.html.twig', [
            'article' => $article,
            'techProducts' => $techProducts,
        ]);
    }

    #[Route('/create', name: 'create')]
    #[IsGranted('ROLE_EDITOR')]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $dto = new CreateArticleDTO();
            $dto->title = $request->request->get('title', '');
            $dto->content = $request->request->get('content', '');
            $dto->slug = $request->request->get('slug');
            $dto->author = $request->request->get('author');
            
            $publishDateStr = $request->request->get('publish_date');
            if ($publishDateStr) {
                $dto->publishDate = new \DateTimeImmutable($publishDateStr);
            }

            try {
                $article = $this->articleService->createArticle($dto, $this->getUser());

                // Handle tech product associations
                $techProductIds = $request->request->all('tech_products');
                if (!empty($techProductIds)) {
                    foreach ($techProductIds as $index => $techProductId) {
                        if (!empty($techProductId)) {
                            $this->articleTechProductService->associateTechProduct(
                                $article,
                                $techProductId,
                                $index
                            );
                        }
                    }
                }

                $this->addFlash('success', 'Article created successfully!');
                return $this->redirectToRoute('admin_article_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating article: ' . $e->getMessage());
            }
        }

        // Get all tech products for selection
        $techProducts = $this->techProductService->getAllTechProducts(1, 1000);

        return $this->render('admin/article/form.html.twig', [
            'article' => null,
            'techProducts' => $techProducts,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(string $id, Request $request): Response
    {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);

        if ($request->isMethod('POST')) {
            $dto = new UpdateArticleDTO();
            $dto->title = $request->request->get('title');
            $dto->content = $request->request->get('content');
            $dto->slug = $request->request->get('slug');
            $dto->author = $request->request->get('author');
            
            $publishDateStr = $request->request->get('publish_date');
            if ($publishDateStr) {
                $dto->publishDate = new \DateTimeImmutable($publishDateStr);
            }

            try {
                $this->articleService->updateArticle($article, $dto);

                // Update tech product associations
                // First, get current associations
                $currentProducts = $this->articleTechProductService->getTechProductsForArticle($article);
                $currentProductIds = array_map(fn($p) => $p->getId(), $currentProducts);

                // Get new associations from form
                $newProductIds = array_filter($request->request->all('tech_products'));

                // Remove products that are no longer selected
                foreach ($currentProductIds as $productId) {
                    if (!in_array($productId, $newProductIds)) {
                        $this->articleTechProductService->dissociateTechProduct($article, $productId);
                    }
                }

                // Add new products
                foreach ($newProductIds as $index => $productId) {
                    if (!in_array($productId, $currentProductIds)) {
                        $this->articleTechProductService->associateTechProduct($article, $productId, $index);
                    } else {
                        // Update sort order
                        $this->articleTechProductService->updateSortOrder($article, $productId, $index);
                    }
                }

                $this->addFlash('success', 'Article updated successfully!');
                return $this->redirectToRoute('admin_article_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating article: ' . $e->getMessage());
            }
        }

        // Get current tech products for this article
        $associatedProducts = $this->articleTechProductService->getTechProductsForArticle($article);
        
        // Get all tech products for selection
        $allTechProducts = $this->techProductService->getAllTechProducts(1, 1000);

        return $this->render('admin/article/form.html.twig', [
            'article' => $article,
            'techProducts' => $allTechProducts,
            'associatedProducts' => $associatedProducts,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id, Request $request): Response
    {
        $article = $this->articleService->getArticleById($id);

        if (!$article) {
            throw $this->createNotFoundException('Article not found');
        }

        $this->denyAccessUnlessGranted(ArticleVoter::DELETE, $article);

        if ($this->isCsrfTokenValid('delete-article-' . $id, $request->request->get('_token'))) {
            try {
                $this->articleService->deleteArticle($article);
                $this->addFlash('success', 'Article deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting article: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_article_list');
    }
}
