<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class BaseController extends AbstractController
{
    public static function validToken(Request $request, JWTEncoderInterface $encoder,EntityManagerInterface $em ):bool
    {
        $payload = $encoder->decode(str_replace('Bearer ', '', $request->headers->get('authorization')));
            
        if(!$payload)
            return false;
            
        if(!$em->getRepository(User::class)->findOneBy(['username' => $payload['username']]))
            return false;
        
        $creation = date('Y-m-d H:i:s', $payload['iat']);
        $expiration = date('Y-m-d H:i:s', $payload['exp']);
            
        if(new \Datetime($expiration) < new \Datetime($creation))
           return false;
        
        return true;
    }
}