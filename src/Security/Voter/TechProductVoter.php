<?php

namespace App\Security\Voter;

use App\Document\TechProduct;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TechProductVoter extends Voter
{
    public const VIEW = 'TECHPRODUCT_VIEW';
    public const EDIT = 'TECHPRODUCT_EDIT';
    public const DELETE = 'TECHPRODUCT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof TechProduct;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // User must be authenticated
        if (!$user instanceof User) {
            return false;
        }

        /** @var TechProduct $techProduct */
        $techProduct = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($techProduct, $user),
            self::EDIT => $this->canEdit($techProduct, $user),
            self::DELETE => $this->canDelete($techProduct, $user),
            default => false,
        };
    }

    private function canView(TechProduct $techProduct, User $user): bool
    {
        // All authenticated users (viewers and above) can view tech products
        return true;
    }

    private function canEdit(TechProduct $techProduct, User $user): bool
    {
        // Admins and editors can edit tech products
        return in_array('ROLE_EDITOR', $user->getRoles())
            || in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function canDelete(TechProduct $techProduct, User $user): bool
    {
        // Only admins can delete tech products
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
