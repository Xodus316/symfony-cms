<?php

namespace App\Entity;

use App\Repository\ArticleTechProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ArticleTechProductRepository::class)]
#[ORM\Table(name: 'article_techproduct')]
#[ORM\UniqueConstraint(name: 'unique_article_techproduct', columns: ['article_id', 'techproduct_mongo_id'])]
#[ORM\Index(columns: ['article_id'], name: 'idx_article_id')]
#[ORM\Index(columns: ['techproduct_mongo_id'], name: 'idx_techproduct_mongo_id')]
class ArticleTechProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    /**
     * MongoDB ObjectId stored as string
     */
    #[ORM\Column(type: Types::STRING, length: 36)]
    private ?string $techproductMongoId = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $sortOrder = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;

        return $this;
    }

    public function getTechproductMongoId(): ?string
    {
        return $this->techproductMongoId;
    }

    public function setTechproductMongoId(string $techproductMongoId): static
    {
        $this->techproductMongoId = $techproductMongoId;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
