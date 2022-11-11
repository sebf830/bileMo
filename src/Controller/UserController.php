<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Client;
use App\Repository\UserRepository;
use App\Validator\EntityValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('/users')]
class UserController extends AbstractController
{

    private EntityManagerInterface $em;
    private EntityValidator $validator;
    private CacheInterface $cache;

    public function __construct(EntityManagerInterface $em, EntityValidator $validator, CacheInterface $cache) {
        $this->em = $em;
        $this->validator = $validator;
        $this->cache = $cache;

    }

    public static function links(){
        return [
            ["name" => "getCollection", "type" => "collection", "verb" => "GET", "href" => '/users'],
            ["name" => "getItem", "type" => "item","verb" => "GET", "href" => '/users']
        ];
    }

    #[Route('/', name: 'app_users_collection', methods: ['GET'])]
    public function getCollection(Request $request, UserRepository $userRepo): JsonResponse
    {
        $params['page'] = (int)$request->get('page') != 0 ?  (int)$request->get('page') : 1;
        $params['per_page'] = (int)$request->get('per_page') != 0 ? (int)$request->get('per_page') : 5;
        $params['offset'] = $params['per_page'] * ($params['page'] - 1);
        $params['embed'] = $request->get('embed') ? $request->get('embed') : [];
        $params['client'] = $request->get('client') ? $request->get('client') : null;

        $usersCount = $this->em->getRepository(User::class)->countApiUsers();

        $cacheName = 'users' . $params['page'] . '-'. $params['per_page'].'-'. implode('-', $params['embed']) .'-'. $params['client'];

        $users = $this->cache->get($cacheName, function(ItemInterface $item) use($userRepo, $params){
            $item->expiresAfter(3600);
            return $userRepo->getApiUsers($params);
        });
    
        $totalPage = $params['per_page'] != null  
        ? ceil(count($usersCount) / $params['per_page']) 
        : ceil(count($usersCount) / 5);

        // remove user passwords from the response
        for($i = 0; $i < count($users); $i++){
            unset($users[$i]['password']);
        }

        return new JsonResponse([
            'statusCode' => 200,
            'status' => 'SUCCESS',
            'currentPage' => $params['page'] != null ? $params['page'] : 1,
            'itemsPerPage' => $params['per_page'] != null ? $params['per_page']: 5,
            'count_items' => count($usersCount),
            'count_pages' => $totalPage,
            'data' => $users
        ], 200);
    }

    #[Route('/{userId}', name: 'app_user_item', methods: ['GET'])]
    public function getItem(Request $request, int $userId, UserRepository $userRepo): JsonResponse
    {
        if(!$userId || $userId == null || intval($userId) < 1){
            return new JsonResponse([
                'statusCode' => 400,
                'status' => 'BAD_REQUEST',
                'message' => "missing or incorrect parameter id"
            ], 400);
        }

        $params['user'] = $userId;
        $params['embed'] = $request->get('embed') ? $request->get('embed') : [];
        $params['client'] = $request->get('client') ? $request->get('client') : null;

        $cacheName = 'user' . $userId .'-'. implode('-', $params['embed']) .'-'. $params['client'];

        $user = $this->cache->get($cacheName, function(ItemInterface $item) use($userRepo, $params){
            $item->expiresAfter(3600);
            return $userRepo->getApiUsers($params);
        });

        if(!$user){
            return new JsonResponse([
                'statusCode' => 404,
                'status' => 'USER_NOT_FOUND',
                'message' => "the request is not found"
            ], 404);
        }

        unset($user[0]['password']);

        return new JsonResponse([
            'statusCode' => 200,
            'status' => 'SUCCESS',
            'data' => $user
        ], 200);
    }

    #[Route('/', name: 'app_user_create', methods: ['POST'])]
    public function create(Request $request,  UserPasswordHasherInterface $hasher): JsonResponse
    {
        $content = json_decode($request->getContent(), true);

        $user = (new User())
        ->setFirstname(isset($content['firstname']) ? $content['firstname'] : "" )
        ->setLastname(isset($content['lastname']) ? $content['lastname'] : "" )
        ->setUsername(isset($content['username']) ? $content['username'] : "" )
        ->setRoles([$content['role']]);

        $user->setPassword($hasher->hashPassword($user, $content['password']));
        
        if($content['client'] && is_int($content['client'])){
            $client = $this->em->getRepository(Client::class)->find($content['client']);

            if(!$client){
                return new JsonResponse([
                    'statusCode' => 404,
                    'status' => 'CLIENT_NOT_FOUND',
                    'message' => "the request ressource is not found"
                ], 404);
            }
            $user->setClient($client);
        }
        $this->em->persist($user);

        if(count($this->validator->validate($user)) > 0){
            return new JsonResponse([
                'statusCode' => 400,
                'status' => 'BAD_REQUEST',
                "message" => "The request is bad",
                'validations' => $this->validator->validate($user)
            ], 400);
        }
        $this->em->flush();

        return new JsonResponse([
            'statusCode' => 201,
            'status' => 'SUCCESS',
            'message' => 'user created successfully',
            'datas' => [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getUsername(),
                'role'=> $user->getRoles(),
                'creation' => (new \Datetime('now'))->format('Y-m-d H:i:s')
            ]
        ], 200);
    }

    #[Route('/{userId}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(Request $request,  UserPasswordHasherInterface $hasher, int $userId): JsonResponse
    {
        if(!$userId || $userId == null || intval($userId) < 1){
            return new JsonResponse([
                'statusCode' => 400,
                'status' => 'BAD_REQUEST',
                'message' => "missing or incorrect parameter id"
            ], 400);
        }

        $user = $this->em->getRepository(User::class)->find($userId);

        if(!$user){
            return new JsonResponse([
                'statusCode' => 404,
                'status' => 'USER_NOT_FOUND',
                'message' => "the request is not found"
            ], 404);
        }

        $this->em->remove($user);
        $this->em->flush();

        return new JsonResponse([
            'statusCode' => 200,
            'status' => 'SUCCESS',
            'message' => "User successfully deleted"
        ], 200);
    }
}
