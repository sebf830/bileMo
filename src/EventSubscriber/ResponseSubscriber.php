<?php

namespace App\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

class ResponseSubscriber implements EventSubscriberInterface
{
    private $em;
    public function __construct(JWTEncoderInterface $encoder, JWTTokenManagerInterface $jwtManager, EntityManagerInterface $em)
    {
        $this->jwtManager = $jwtManager;
        $this->encoder = $encoder;
        $this->em = $em;
    }
    
    public function onKernelResponse(ResponseEvent $event)
    {
        $response = $event->getResponse();

        if ($response instanceof JsonResponse) {
            
            $content = json_decode($response->getContent(), true);
            $controller = explode('::', $event->getRequest()->get('_controller'))[0];
            $currentMethod = explode('::', $event->getRequest()->get('_controller'))[1];
            
            if($controller != "App\Controller\SecurityController" && isset($content['datas'])){
                foreach($content['datas'] as $key => $data){
                    foreach($controller::links() as $link){

                        $type = $link['name'] == $currentMethod ? "self" : $link['type'];
                        
                        $content['datas'][$key]['links'][$type] = [
                                "href" => $link['type'] == 'item' ? $link['href'].'/'. $data['id'] : $link['href'],
                                "method" => $link['verb']
                            ];
                    }
                }
            }
            // Update response
            $response->setContent(json_encode($content));
        }
    }


    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if($exception instanceof AccessDeniedHttpException){
            $response['statusCode'] = Response::HTTP_FORBIDDEN;
            $response['status'] = "ACCESS_DENIED";
        }
        elseif($exception instanceof UnauthorizedHttpException || $exception instanceof JWTDecodeFailureException){
            $response['statusCode'] = Response::HTTP_UNAUTHORIZED;
            $response['status'] = "UNAUTHORIZED";
        }else{
            $response['statusCode'] = Response::HTTP_INTERNAL_SERVER_ERROR;
            $response['status'] = "INTERNAL_SERVER_ERROR";
        }

        $response['error'] = $exception->getMessage();
        $response['trace'] = $exception->getTrace();

        $jsonResponse = new JsonResponse($response);
        $event->setResponse($jsonResponse);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
