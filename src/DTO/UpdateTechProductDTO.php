<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateTechProductDTO
{
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Product ID must be at least {{ limit }} characters',
        maxMessage: 'Product ID cannot be longer than {{ limit }} characters'
    )]
    public ?string $productId = null;

    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public ?string $name = null;

    /**
     * Flexible specs - can be any structure
     * @var array<string, mixed>|null
     */
    #[Assert\Type('array')]
    public ?array $specs = null;

    /**
     * @var array{currency: string, amount: float}|null
     */
    #[Assert\Type('array')]
    public ?array $pricing = null;
}
