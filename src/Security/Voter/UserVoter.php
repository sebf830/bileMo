<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class UserVoter extends Voter
{
    public const VIEW_USERS = 'VIEW_USERS';
    public const VIEW_USER = 'VIEW_USER';
    public const DELETE_USER = 'DELETE_USER';
    public const CREATE_USER = 'CREATE_USER';
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::VIEW_USERS, self::VIEW_USER, self::CREATE_USER, self::DELETE_USER]);
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token = null): bool
    {
        // get the current user from the JWT payload
        $user = $this->userService->getCurrentUser();

        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW_USERS:
                // admin and client access
                if(in_array('ROLE_CLIENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()))
                    return true;

                break;
            case self::VIEW_USER:
                // admin can see all users
                if(in_array('ROLE_ADMIN', $user->getRoles())){
                    return true;
                }
                // clients can see own users
                elseif(in_array('ROLE_CLIENT', $user->getRoles())){
                    return $subject->getClient() == $user->getClient();
                }
                // users can see their own informations
                else{
                    return $user->getId() == $subject->getId();
                }

                break;
            case self::CREATE_USER:
                // admin and client access
                if(in_array('ROLE_CLIENT', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles()))
                    return true;
                break;

            case self::DELETE_USER:
                // admin access
                if(in_array('ROLE_ADMIN', $user->getRoles()))
                    return true;

                // client access
                if(in_array('ROLE_CLIENT', $user->getRoles()))
                    return $subject->getClient() == $user->getClient();

                break;
        }

        return false;
    }
}
