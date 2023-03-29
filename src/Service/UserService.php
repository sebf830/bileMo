<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

class UserService{

    private $request;
    private $encoder;
    private $em;
    
    public function __construct(RequestStack $request, JWTEncoderInterface $encoder, EntityManagerInterface $em){
        $this->request = $request;
        $this->encoder = $encoder;
        $this->em = $em;
    }

    public function getCurrentUser():User{
 
        $payload = $this->getPayload();

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => $payload['username']]);
        
        return $user;
    }

    public function getPayload():array{
        $token = str_replace('Bearer ', '', $this->request->getCurrentRequest()->headers->get('authorization'));

        return  $this->encoder->decode($token);
    }
}