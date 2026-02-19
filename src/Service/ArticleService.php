<?php

namespace App\Service;

use App\DTO\CreateArticleDTO;
use App\DTO\UpdateArticleDTO;
use App\Entity\Article;
use App\Entity\User;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;

class ArticleService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ArticleRepository $articleRepository
    ) {
    }

    /**
     * Create a new article
     */
    public function createArticle(CreateArticleDTO $dto, User $user): Article
    {
        $article = new Article();
        $article->setTitle($dto->title);
        $article->setContent($dto->content);
        $article->setAuthor($dto->author);
        $article->setUser($user);

        if ($dto->slug) {
            $article->setSlug($dto->slug);
        }

        if ($dto->publishDate) {
            $article->setPublishDate($dto->publishDate);
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    /**
     * Update an existing article
     */
    public function updateArticle(Article $article, UpdateArticleDTO $dto): Article
    {
        if ($dto->title !== null) {
            $article->setTitle($dto->title);
            // Slug will be regenerated via lifecycle callback
            if ($dto->slug) {
                $article->setSlug($dto->slug);
            } else {
                // Reset slug to trigger regeneration
                $article->setSlug('');
            }
        }

        if ($dto->content !== null) {
            $article->setContent($dto->content);
        }

        if ($dto->author !== null) {
            $article->setAuthor($dto->author);
        }

        if ($dto->publishDate !== null) {
            $article->setPublishDate($dto->publishDate);
        }

        if ($dto->slug !== null && $dto->title === null) {
            $article->setSlug($dto->slug);
        }

        $this->entityManager->flush();

        return $article;
    }

    /**
     * Delete an article
     */
    public function deleteArticle(Article $article): void
    {
        $this->entityManager->remove($article);
        $this->entityManager->flush();
    }

    /**
     * Get article by ID
     */
    public function getArticleById(int $id): ?Article
    {
        return $this->articleRepository->find($id);
    }

    /**
     * Get article by slug
     */
    public function getArticleBySlug(string $slug): ?Article
    {
        return $this->articleRepository->findOneBySlug($slug);
    }

    /**
     * Get all articles with pagination
     */
    public function getAllArticles(int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        return $this->articleRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get published articles with pagination
     */
    public function getPublishedArticles(int $page = 1, int $limit = 10): array
    {
        return $this->articleRepository->findPublished($page, $limit);
    }

    /**
     * Get total count of articles
     */
    public function getTotalCount(): int
    {
        return $this->articleRepository->count([]);
    }

    /**
     * Get total count of published articles
     */
    public function getPublishedCount(): int
    {
        return $this->articleRepository->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.publishDate IS NOT NULL')
            ->andWhere('a.publishDate <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }
}
