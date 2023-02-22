<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductVoter extends Voter
{
    public const VIEW_PRODUCT = 'VIEW_PRODUCT';
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::VIEW_PRODUCT]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // get the current user from the JWT payload
        $user = $this->userService->getCurrentUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW_PRODUCT:
                
                if(in_array('ROLE_CLIENT', $user->getRoles()))
                    return true;
                break;
        }

        return false;
    }
}
