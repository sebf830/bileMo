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
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

class JWTSubscriber implements EventSubscriberInterface
{
    private $em;
    private $userService;
    public const USER_ROUTES = ['app_user_item', 'app_user_delete'];
    public const PUBLIC_ROUTES = ['app.swagger', 'app.swagger_ui', 'app_login'];
    public function __construct(UserService $userService, JWTEncoderInterface $encoder, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $em)
    {
        $this->jwtManager = $jwtManager;
        $this->encoder = $encoder;
        $this->em = $em;
        $this->userService = $userService;
    }

    public function onKernelRequest(RequestEvent $event)
    {       
        if(!in_array($event->getRequest()->get('_route'),  self::PUBLIC_ROUTES)){

            $user = $this->userService->getCurrentUser();

            if(!$user){
                throw new JWTDecodeFailureException('invalid user', 'invalid credentials');
            }
            
            $payload = $this->userService->getPayload();
            $expiration = date('Y-m-d H:i:s', $payload['exp']);
            
            if(new \Datetime('now') > new \Datetime($expiration)){
                // throw new JWTDecodeFailureException('expired', 'Your token is expired');
                throw new UnauthorizedHttpException('', 'Your token is expired', null, 401);
            }
        }
    }


    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }
}
