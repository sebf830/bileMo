<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class UserSubscriber implements EventSubscriberInterface
{
    private $em;
    private $userService;
    public const USER_ROUTES = ['app_user_item', 'app_user_delete'];

    public function __construct(UserService $userService, JWTEncoderInterface $encoder, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $em)
    {
        $this->jwtManager = $jwtManager;
        $this->encoder = $encoder;
        $this->em = $em;
        $this->userService = $userService;
    }

    public function onUserRequest(RequestEvent $event){

        if(in_array($event->getRequest()->get('_route'), self::USER_ROUTES)){

            $currentUser = $this->userService->getCurrentUser();
            $payload = $this->userService->getPayload();
            $reqUser = $this->em->getRepository(User::class)->find($event->getRequest()->attributes->get('_route_params')['userId']);

            if(!$currentUser->getClient() || !$currentUser->getClient()->getUsers()->contains($reqUser)){
                throw new UnauthorizedHttpException('', 'cannot access this resource', null, 401);
            }
             
            if(!in_array('ROLE_CLIENT', $payload['roles']) && !in_array('ROLE_ADMIN', $payload['roles'])){
                throw new UnauthorizedHttpException('', 'cannot access this resource', null, 401);
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onUserRequest'
        ];
    }
}
