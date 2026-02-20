<?php

namespace App\Controller\Admin;

use App\Document\TechProduct;
use App\DTO\CreateTechProductDTO;
use App\DTO\UpdateTechProductDTO;
use App\Security\Voter\TechProductVoter;
use App\Service\TechProductService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/techproducts', name: 'admin_techproduct_')]
#[IsGranted('ROLE_VIEWER')]
class TechProductAdminController extends AbstractController
{
    public function __construct(
        private readonly TechProductService $techProductService
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $techProducts = $this->techProductService->getAllTechProducts($page, $limit);
        $total = $this->techProductService->getTotalCount();

        return $this->render('admin/techproduct/list.html.twig', [
            'techProducts' => $techProducts,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => (int) ceil($total / $limit),
        ]);
    }

    #[Route('/create', name: 'create')]
    #[IsGranted('ROLE_EDITOR')]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $dto = new CreateTechProductDTO();
            $dto->productId = $request->request->get('product_id', '');
            $dto->name = $request->request->get('name', '');
            
            // Parse JSON specs
            $specsJson = $request->request->get('specs', '{}');
            try {
                $dto->specs = json_decode($specsJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->addFlash('error', 'Invalid JSON in specs field: ' . $e->getMessage());
                return $this->render('admin/techproduct/form.html.twig', [
                    'techProduct' => null,
                    'specsJson' => $specsJson,
                ]);
            }
            
            // Parse JSON pricing if provided
            $pricingJson = $request->request->get('pricing', '');
            if (!empty($pricingJson)) {
                try {
                    $dto->pricing = json_decode($pricingJson, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $this->addFlash('error', 'Invalid JSON in pricing field: ' . $e->getMessage());
                    return $this->render('admin/techproduct/form.html.twig', [
                        'techProduct' => null,
                        'specsJson' => $specsJson,
                        'pricingJson' => $pricingJson,
                    ]);
                }
            }

            try {
                $this->techProductService->createTechProduct($dto);
                $this->addFlash('success', 'Tech product created successfully!');
                return $this->redirectToRoute('admin_techproduct_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating tech product: ' . $e->getMessage());
            }
        }

        return $this->render('admin/techproduct/form.html.twig', [
            'techProduct' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(string $id, Request $request): Response
    {
        $techProduct = $this->techProductService->getTechProductById($id);

        if (!$techProduct) {
            throw $this->createNotFoundException('Tech product not found');
        }

        $this->denyAccessUnlessGranted(TechProductVoter::EDIT, $techProduct);

        if ($request->isMethod('POST')) {
            $dto = new UpdateTechProductDTO();
            $dto->productId = $request->request->get('product_id');
            $dto->name = $request->request->get('name');
            
            // Parse JSON specs
            $specsJson = $request->request->get('specs', '{}');
            try {
                $dto->specs = json_decode($specsJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->addFlash('error', 'Invalid JSON in specs field: ' . $e->getMessage());
                return $this->render('admin/techproduct/form.html.twig', [
                    'techProduct' => $techProduct,
                    'specsJson' => $specsJson,
                ]);
            }
            
            // Parse JSON pricing if provided
            $pricingJson = $request->request->get('pricing', '');
            if (!empty($pricingJson)) {
                try {
                    $dto->pricing = json_decode($pricingJson, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    $this->addFlash('error', 'Invalid JSON in pricing field: ' . $e->getMessage());
                    return $this->render('admin/techproduct/form.html.twig', [
                        'techProduct' => $techProduct,
                        'specsJson' => $specsJson,
                        'pricingJson' => $pricingJson,
                    ]);
                }
            }

            try {
                $this->techProductService->updateTechProduct($techProduct, $dto);
                $this->addFlash('success', 'Tech product updated successfully!');
                return $this->redirectToRoute('admin_techproduct_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating tech product: ' . $e->getMessage());
            }
        }

        return $this->render('admin/techproduct/form.html.twig', [
            'techProduct' => $techProduct,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id, Request $request): Response
    {
        $techProduct = $this->techProductService->getTechProductById($id);

        if (!$techProduct) {
            throw $this->createNotFoundException('Tech product not found');
        }

        $this->denyAccessUnlessGranted(TechProductVoter::DELETE, $techProduct);

        if ($this->isCsrfTokenValid('delete-techproduct-' . $id, $request->request->get('_token'))) {
            try {
                $this->techProductService->deleteTechProduct($techProduct);
                $this->addFlash('success', 'Tech product deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting tech product: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_techproduct_list');
    }

    #[Route('/{id}', name: 'show')]
    public function show(string $id): Response
    {
        $techProduct = $this->techProductService->getTechProductById($id);

        if (!$techProduct) {
            throw $this->createNotFoundException('Tech product not found');
        }

        $this->denyAccessUnlessGranted(TechProductVoter::VIEW, $techProduct);

        return $this->render('admin/techproduct/show.html.twig', [
            'techProduct' => $techProduct,
        ]);
    }
}
