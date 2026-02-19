<?php

namespace App\Document;

use App\Repository\TechProductRepository;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Serializer\Attribute\Groups;

#[MongoDB\Document(collection: 'tech_products', repositoryClass: TechProductRepository::class)]
#[MongoDB\Index(keys: ['productId' => 'asc'], options: ['unique' => true])]
#[MongoDB\Index(keys: ['name' => 'text'])]
#[MongoDB\HasLifecycleCallbacks]
class TechProduct
{
    #[MongoDB\Id]
    #[Groups(['techproduct:read'])]
    private ?string $id = null;

    #[MongoDB\Field(type: 'string')]
    #[Groups(['techproduct:read', 'techproduct:write'])]
    private ?string $productId = null;

    #[MongoDB\Field(type: 'string')]
    #[Groups(['techproduct:read', 'techproduct:write'])]
    private ?string $name = null;

    /**
     * Flexible specs field - can store any structure
     * @var array<string, mixed>
     */
    #[MongoDB\Field(type: 'hash')]
    #[Groups(['techproduct:read', 'techproduct:write'])]
    private array $specs = [];

    /**
     * @var array{currency: string, amount: float}|null
     */
    #[MongoDB\Field(type: 'hash')]
    #[Groups(['techproduct:read', 'techproduct:write'])]
    private ?array $pricing = null;

    #[MongoDB\Field(type: 'date_immutable')]
    #[Groups(['techproduct:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[MongoDB\Field(type: 'date_immutable')]
    #[Groups(['techproduct:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[MongoDB\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSpecs(): array
    {
        return $this->specs;
    }

    /**
     * @param array<string, mixed> $specs
     */
    public function setSpecs(array $specs): static
    {
        $this->specs = $specs;

        return $this;
    }

    /**
     * Add or update a single spec
     */
    public function setSpec(string $key, mixed $value): static
    {
        $this->specs[$key] = $value;

        return $this;
    }

    /**
     * Get a single spec value
     */
    public function getSpec(string $key): mixed
    {
        return $this->specs[$key] ?? null;
    }

    /**
     * @return array{currency: string, amount: float}|null
     */
    public function getPricing(): ?array
    {
        return $this->pricing;
    }

    /**
     * @param array{currency: string, amount: float}|null $pricing
     */
    public function setPricing(?array $pricing): static
    {
        $this->pricing = $pricing;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
