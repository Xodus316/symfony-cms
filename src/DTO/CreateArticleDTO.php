<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreateArticleDTO
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Title must be at least {{ limit }} characters',
        maxMessage: 'Title cannot be longer than {{ limit }} characters'
    )]
    public string $title;

    #[Assert\NotBlank(message: 'Content is required')]
    #[Assert\Length(
        min: 10,
        minMessage: 'Content must be at least {{ limit }} characters'
    )]
    public string $content;

    #[Assert\NotBlank(message: 'Author is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Author must be at least {{ limit }} characters',
        maxMessage: 'Author cannot be longer than {{ limit }} characters'
    )]
    public string $author;

    #[Assert\Type(\DateTimeImmutable::class)]
    public ?\DateTimeImmutable $publishDate = null;

    public ?string $slug = null;
}
