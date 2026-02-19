<?php

namespace App\Controller\Admin;

use App\Repository\ArticleRepository;
use App\Repository\TechProductRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_VIEWER')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly TechProductRepository $techProductRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route('', name: 'dashboard')]
    public function index(): Response
    {
        $articleCount = $this->articleRepository->count();
        $techProductCount = $this->techProductRepository->createQueryBuilder()
            ->count()
            ->getQuery()
            ->execute();
        $userCount = $this->userRepository->count();

        // Get recent articles (last 5)
        $recentArticles = $this->articleRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        // Get recent tech products (last 5)
        $recentTechProducts = $this->techProductRepository->createQueryBuilder()
            ->sort('createdAt', 'DESC')
            ->limit(5)
            ->getQuery()
            ->execute()
            ->toArray();

        return $this->render('admin/dashboard.html.twig', [
            'articleCount' => $articleCount,
            'techProductCount' => $techProductCount,
            'userCount' => $userCount,
            'recentArticles' => $recentArticles,
            'recentTechProducts' => $recentTechProducts,
        ]);
    }
}
