<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResponseSubscriber implements EventSubscriberInterface
{
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

        $response = new JsonResponse([
            'statusCode' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'status'=> "INTERNAL_ERROR",
            'message' => $exception->getMessage(),
            "trace" =>  $exception->getTrace(),
        ], 500);
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
