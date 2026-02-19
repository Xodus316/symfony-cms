<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateTechProductDTO
{
    #[Assert\NotBlank(message: 'Product ID is required')]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Product ID must be at least {{ limit }} characters',
        maxMessage: 'Product ID cannot be longer than {{ limit }} characters'
    )]
    public string $productId;

    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
        maxMessage: 'Name cannot be longer than {{ limit }} characters'
    )]
    public string $name;

    /**
     * Flexible specs - can be any structure
     * @var array<string, mixed>
     */
    #[Assert\Type('array')]
    public array $specs = [];

    /**
     * @var array{currency: string, amount: float}|null
     */
    #[Assert\Type('array')]
    public ?array $pricing = null;
}
