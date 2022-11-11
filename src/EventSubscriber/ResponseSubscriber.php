<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResponseSubscriber implements EventSubscriberInterface
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        
        if ($response instanceof JsonResponse) {
            
            $content = json_decode($response->getContent(), true);
            
            $controller = explode('::', $event->getRequest()->get('_controller'))[0];
            $currentMethod = explode('::', $event->getRequest()->get('_controller'))[1];
            
            foreach($content['data'] as $key => $data){

                foreach($controller::links() as $link){

                    $type = $link['name'] == $currentMethod ? "self" : $link['type'];
                    
                    $content['data'][$key]['links'][$type] = [
                            "href" => $link['type'] == 'item' ? $link['href'].'/'. $data['id'] : $link['href'],
                            "method" => $link['verb']
                        ];
                }

            }
            // Update response
            $response->setContent(json_encode($content));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
