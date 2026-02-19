<?php

namespace App\Security\Voter;

use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleVoter extends Voter
{
    public const VIEW = 'ARTICLE_VIEW';
    public const EDIT = 'ARTICLE_EDIT';
    public const DELETE = 'ARTICLE_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Article;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // User must be authenticated
        if (!$user instanceof User) {
            return false;
        }

        /** @var Article $article */
        $article = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($article, $user),
            self::EDIT => $this->canEdit($article, $user),
            self::DELETE => $this->canDelete($article, $user),
            default => false,
        };
    }

    private function canView(Article $article, User $user): bool
    {
        // All authenticated users can view articles
        // Could add logic for draft articles only visible to authors/editors
        if ($article->getPublishDate() === null) {
            // Unpublished articles - only author, editors, and admins
            return $this->isOwner($article, $user)
                || in_array('ROLE_EDITOR', $user->getRoles())
                || in_array('ROLE_ADMIN', $user->getRoles());
        }

        return true;
    }

    private function canEdit(Article $article, User $user): bool
    {
        // Admins can edit any article
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Editors can edit any article
        if (in_array('ROLE_EDITOR', $user->getRoles())) {
            return true;
        }

        // Author can edit their own article
        return $this->isOwner($article, $user);
    }

    private function canDelete(Article $article, User $user): bool
    {
        // Only admins can delete articles
        return in_array('ROLE_ADMIN', $user->getRoles());
    }

    private function isOwner(Article $article, User $user): bool
    {
        return $article->getUser()?->getId() === $user->getId();
    }
}
