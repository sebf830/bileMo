<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Annotations as OA;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'app_login', methods:['POST'])]
    public function login(
        JWTTokenManagerInterface $JWTManager, 
        Request $request, 
        UserPasswordHasherInterface $passwordEncoder,
        EntityManagerInterface $em,
        ): JsonResponse
    {

        $credentials = json_decode($request->getContent(), true);
        $username = isset($credentials['username']) ? $credentials['username'] : null;
        $password = isset($credentials['password']) ? $credentials['password'] : null;

        if (null == $username || null == $password) {
            return new JsonResponse([
                "statusCode" => 400,
                "status" => "BAD_CREDENTIALS",
                "message" => "invalid credentials"
            ], 400);
        }

        $user = $em->getRepository(User::class)->findOneBy(['username' => $username]);

        if (!$user || !$passwordEncoder->isPasswordValid($user, $password)) {
            return new JsonResponse([
                "statusCode" => 400,
                "status" => "BAD_CREDENTIALS",
                "message" => "invalid credentials"
            ], 400);
        }

        $token = $JWTManager->create($user);

        return new JsonResponse([
            'statusCode' => 200,
            'status' => 'SUCCESS',
            'message' => 'authenticated successfully',
            'datas' => [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getUsername(),
                'role'=> $user->getRoles(),
                'connexion' => (new \Datetime('now'))->format('Y-m-d H:i:s'),
                'token' => $token
            ]
        ], 200);
    }
}
