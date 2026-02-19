<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users', name: 'admin_user_')]
#[IsGranted('ROLE_ADMIN')]
class UserAdminController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    #[Route('', name: 'list')]
    public function list(): Response
    {
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/user/list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setEmail($request->request->get('email'));
            
            // Set password
            $plainPassword = $request->request->get('password');
            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            } else {
                $this->addFlash('error', 'Password is required');
                return $this->render('admin/user/form.html.twig', ['user' => null]);
            }
            
            // Set roles
            $roles = $request->request->all('roles');
            if (!empty($roles)) {
                $user->setRoles(array_values($roles));
            }

            try {
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $this->addFlash('success', 'User created successfully!');
                return $this->redirectToRoute('admin_user_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating user: ' . $e->getMessage());
            }
        }

        return $this->render('admin/user/form.html.twig', [
            'user' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            
            // Update password only if provided
            $plainPassword = $request->request->get('password');
            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            // Update roles
            $roles = $request->request->all('roles');
            if (!empty($roles)) {
                $user->setRoles(array_values($roles));
            } else {
                // If no roles selected, set to ROLE_VIEWER
                $user->setRoles(['ROLE_VIEWER']);
            }

            try {
                $this->entityManager->flush();
                $this->addFlash('success', 'User updated successfully!');
                return $this->redirectToRoute('admin_user_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating user: ' . $e->getMessage());
            }
        }

        return $this->render('admin/user/form.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Prevent deleting yourself
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'You cannot delete your own account');
            return $this->redirectToRoute('admin_user_list');
        }

        if ($this->isCsrfTokenValid('delete-user-' . $id, $request->request->get('_token'))) {
            try {
                $this->entityManager->remove($user);
                $this->entityManager->flush();
                $this->addFlash('success', 'User deleted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error deleting user: ' . $e->getMessage());
            }
        }

        return $this->redirectToRoute('admin_user_list');
    }
}
