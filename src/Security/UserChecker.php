<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        if ($user->isSuspended()) {
            throw new CustomUserMessageAccountStatusException('Ce compte est suspendu.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        
    }
}
