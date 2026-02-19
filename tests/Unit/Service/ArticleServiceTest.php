<?php

namespace App\Tests\Unit\Service;

use App\DTO\CreateArticleDTO;
use App\DTO\UpdateArticleDTO;
use App\Entity\Article;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Service\ArticleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ArticleServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private ArticleRepository&MockObject $articleRepository;
    private ArticleService $articleService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->articleRepository = $this->createMock(ArticleRepository::class);
        $this->articleService = new ArticleService(
            $this->entityManager,
            $this->articleRepository
        );
    }

    public function testCreateArticle(): void
    {
        $dto = new CreateArticleDTO();
        $dto->title = 'Test Article';
        $dto->content = 'Test content for article';
        $dto->author = 'Test Author';

        $user = new User();
        $user->setEmail('test@example.com');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Article::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $article = $this->articleService->createArticle($dto, $user);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Test Article', $article->getTitle());
        $this->assertEquals('Test content for article', $article->getContent());
        $this->assertSame($user, $article->getUser());
    }

    public function testUpdateArticle(): void
    {
        $article = new Article();
        $article->setTitle('Original Title');
        $article->setContent('Original Content');

        $dto = new UpdateArticleDTO();
        $dto->title = 'Updated Title';
        $dto->content = 'Updated Content';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $updatedArticle = $this->articleService->updateArticle($article, $dto);

        $this->assertEquals('Updated Title', $updatedArticle->getTitle());
        $this->assertEquals('Updated Content', $updatedArticle->getContent());
    }

    public function testGetArticleById(): void
    {
        $article = new Article();
        $article->setTitle('Test Article');

        $this->articleRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($article);

        $result = $this->articleService->getArticleById(1);

        $this->assertSame($article, $result);
    }

    public function testGetArticleBySlug(): void
    {
        $article = new Article();
        $article->setTitle('Test Article');

        $this->articleRepository->expects($this->once())
            ->method('findOneBySlug')
            ->with('test-article')
            ->willReturn($article);

        $result = $this->articleService->getArticleBySlug('test-article');

        $this->assertSame($article, $result);
    }

    public function testDeleteArticle(): void
    {
        $article = new Article();
        $article->setTitle('Test Article');

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($article);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->articleService->deleteArticle($article);
    }
}
