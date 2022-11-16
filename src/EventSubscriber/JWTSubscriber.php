<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTDecodedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

class JWTSubscriber implements EventSubscriberInterface
{
    private $em;
    public function __construct(JWTEncoderInterface $encoder, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $em)
    {
        $this->jwtManager = $jwtManager;
        $this->encoder = $encoder;
        $this->em = $em;
    }

    public function onKernelRequest(RequestEvent $event)
    {        
        // if($event->getRequest()->get('_route') != 'app_login'){
        //     $token = str_replace('Bearer ', '', $event->getRequest()->headers->get('authorization'));
            
        //     $payload = $this->encoder->decode($token);
            
        //     if(!$payload){
        //         return new JsonResponse([
        //             "statusCode" => 401,
        //             "status" => "UNAUTHORIZED",
        //             "message" => "JWT invalid"
        //         ], 401);
        //     }

        //     $user = $this->em->getRepository(User::class)->findOneBy(['username' => $payload['username']]);

        //     if(!$user){
        //         return new JsonResponse([
        //             "statusCode" => 401,
        //             "status" => "UNAUTHORIZED",
        //             "message" => "JWT invalid"
        //         ], 401);
        //     }
            
        //     $creation = date('Y-m-d H:i:s', $payload['iat']);
        //     $expiration = date('Y-m-d H:i:s', $payload['exp']);
            
        //     if(new \Datetime($creation) < new \Datetime($expiration)){
        //         return new JsonResponse([
        //             "statusCode" => 401,
        //             "status" => "UNAUTHORIZED",
        //             "message" => "JWT expired"
        //         ], 401);
        //     }
        //     // dd($payload);
        // }

    }

    public static function getSubscribedEvents(): array
    {
        return [
            // KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
