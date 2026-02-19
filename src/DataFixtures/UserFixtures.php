<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const EDITOR_USER_REFERENCE = 'editor-user';
    public const VIEWER_USER_REFERENCE = 'viewer-user';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create Admin User
        $admin = new User();
        $admin->setEmail('admin@symfony-cms.local');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $manager->persist($admin);
        $this->addReference(self::ADMIN_USER_REFERENCE, $admin);

        // Create Editor User
        $editor = new User();
        $editor->setEmail('editor@symfony-cms.local');
        $editor->setRoles(['ROLE_EDITOR']);
        $editor->setPassword(
            $this->passwordHasher->hashPassword($editor, 'editor123')
        );
        $manager->persist($editor);
        $this->addReference(self::EDITOR_USER_REFERENCE, $editor);

        // Create Viewer User
        $viewer = new User();
        $viewer->setEmail('viewer@symfony-cms.local');
        $viewer->setRoles(['ROLE_VIEWER']);
        $viewer->setPassword(
            $this->passwordHasher->hashPassword($viewer, 'viewer123')
        );
        $manager->persist($viewer);
        $this->addReference(self::VIEWER_USER_REFERENCE, $viewer);

        $manager->flush();
    }
}
