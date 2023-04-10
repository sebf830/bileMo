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
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ResponseSubscriber implements EventSubscriberInterface
{
    private $em;
    private $jwtManager;
    private $encoder;
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

            // extract method
            $controller = explode('::', $event->getRequest()->get('_controller'))[0];
            $currentMethod = explode('::', $event->getRequest()->get('_controller'))[1];
            
            if($controller != "App\Controller\SecurityController" && isset($content['datas'])){
                foreach($content['datas'] as $key => $data){
                    foreach($controller::links() as $link){

                        // rename the current route method
                        $type = $link['name'] == $currentMethod ? "self" : $link['type'];
                        // add link
                        $content['datas'][$key]['links'][$type] = [
                                "href" => $link['type'] == 'item' ? $link['href'].'/'. $data['id'] : $link['href'],
                                "method" => $link['verb']
                            ];
                    }
                }
            }
            // update response
            $response->setContent(json_encode($content));
        }
    }


    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

            if($exception instanceof AccessDeniedHttpException || $exception->getPrevious() instanceof InsufficientAuthenticationException){
                $response['statusCode'] = Response::HTTP_FORBIDDEN;
                $response['status'] = "ACCESS_DENIED";
                $code = 403;
            }
            elseif($exception instanceof JWTDecodeFailureException){
                $response['statusCode'] = Response::HTTP_UNAUTHORIZED;
                $response['status'] = "UNAUTHORIZED";
                $code = 401;
            }
            elseif($exception instanceof UnauthorizedHttpException){
                $response['statusCode'] = Response::HTTP_UNAUTHORIZED;
                $response['status'] = "UNAUTHORIZED";
                $code = 401;


            }else{
                $response['statusCode'] = Response::HTTP_INTERNAL_SERVER_ERROR;
                $response['status'] = "INTERNAL_SERVER_ERROR";
                $code = 500;

            }

        $response['message'] = $exception->getMessage() ? $exception->getMessage() : "no message";
        

        $jsonResponse = new JsonResponse($response, $code);
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
