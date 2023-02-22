<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Client;
use App\Entity\Product;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Validator\EntityValidator;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[OA\Tag(name: 'users')]
#[Route('/api/users')]
class UserController extends AbstractController
{
    private EntityManagerInterface $em;
    private EntityValidator $validator;
    private CacheInterface $cache;
    private UserService $userService;

    public function __construct(EntityManagerInterface $em, 
    EntityValidator $validator, 
    CacheInterface $cache, 
    JWTEncoderInterface $encoder, 
    UserService $userService) {
        $this->em = $em;
        $this->validator = $validator;
        $this->cache = $cache;
        $this->encoder = $encoder;
        $this->userService = $userService;
    }

    public static function links(){
        return [
            ["name" => "getCollection", "type" => "collection", "verb" => "GET", "href" => '/users'],
            ["name" => "getItem", "type" => "item","verb" => "GET", "href" => '/users']
        ];
    }


    /**
     * Get a collection of users, allow a client to access his own users.
     *
     */
    #[OA\Response(
        response: 200,
        description: 'Successful operation',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userItem']))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'unauthorized',
    )]
    #[OA\Response(
        response: 403,
        description: 'access denied',
    )]
    #[OA\Response(
        response: 404,
        description: 'not found',
    )]
    #[OA\Parameter(
        name: 'embed',
        in: 'query',
        description: 'allow user to get a relation datas (?embed=client)',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/', name: 'app_users_collection', methods: ['GET'])]
    public function getCollection(Request $request, UserRepository $userRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW_USERS');

        // get current user
        $user = $this->userService->getCurrentUser();

        // user param
        $params['client'] = $user->getClient()->getId() ? $user->getClient()->getId() : null;
 
        // query params
        $params['embed'] = $request->get('embed') ? $request->get('embed') : [];

        // pagination filter
        $params['page'] = (int)$request->get('page') != 0 ?  (int)$request->get('page') : 1;
        $params['per_page'] = (int)$request->get('per_page') != 0 ? (int)$request->get('per_page') : 5;
        $params['offset'] = $params['per_page'] * ($params['page'] - 1);

        // get users number
        $usersCount = $this->em->getRepository(User::class)->countApiUsers($params);

        // id cache
        $cacheName = 'users' . $params['page'] . '-'. $params['per_page'].'-'. implode('-', $params['embed']) .'-'. $params['client'];

        // get users
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
            'datas' => $users
        ], 200);
    }


    /**
     *  Get a user
     */
    #[OA\Response(
        response: 200,
        description: 'Returns a User object',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['userItem']))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'unauthorized',
    )]
    #[OA\Response(
        response: 403,
        description: 'access denied',
    )]
    #[OA\Response(
        response: 404,
        description: 'not found',
    )]
    #[OA\Parameter(
        name: 'embed',
        in: 'query',
        description: 'allow user to get a relation datas (?embed=client)',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/{userId}', name: 'app_user_item', methods: ['GET'])]
    public function getItem(Request $request, int $userId, UserRepository $userRepo, ): JsonResponse
    {
        $requestUser = $this->em->getRepository(User::class)->find($userId);

        // check user 
        if(!$requestUser){
            return new JsonResponse([
                'statusCode' => 404,
                'status' => 'NOT_FOUND',
                'message' => "The requested user is not found"
            ], 400);
        }

        $this->denyAccessUnlessGranted('VIEW_USER', $requestUser);

        // get current user
        $user = $this->userService->getCurrentUser();

        $params['user'] = $userId;
        $params['embed'] = $request->get('embed') ? $request->get('embed') : [];
        $params['client'] = $user->getClient()->getId() ? $user->getClient()->getId() : null;
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
            'datas' => $user
        ], 200);
    }



    /**
     * Allow a client to create a new user
     *
     */
    #[Route('/', name: 'app_user_create', methods: ['POST'])]
    public function create(Request $request,  UserPasswordHasherInterface $hasher): JsonResponse
    {
        $this->denyAccessUnlessGranted('CREATE_USER');

        $currentUser = $this->userService->getCurrentUser();

        $content = json_decode($request->getContent(), true);
        
        $user = (new User())
        ->setFirstname(isset($content['firstname']) ? $content['firstname'] : "" )
        ->setLastname(isset($content['lastname']) ? $content['lastname'] : "" )
        ->setUsername(isset($content['username']) ? $content['username'] : "" )
        ->setClient($currentUser->getClient())
        ->setRoles(['ROLE_CLIENT_USER']);
        
        $user->setPassword($hasher->hashPassword($user, $content['password']));
        
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
            'data' => [
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getUsername(),
                'role'=> $user->getRoles()[0],
                'creation' => (new \Datetime('now'))->format('Y-m-d H:i:s')
            ]
        ], 200);
    }


    /**
     *  Allow a client to delete a user
     */
    #[OA\Response(
        response: 200,
        description: 'delete a user',
    )]
    #[OA\Response(
        response: 401,
        description: 'unauthorized',
    )]
    #[OA\Response(
        response: 403,
        description: 'access denied',
    )]
    #[OA\Response(
        response: 404,
        description: 'not found',
    )]
    #[Route('/{userId}', name: 'app_user_delete', methods: ['DELETE'])]
    public function delete(Request $request,  UserPasswordHasherInterface $hasher, int $userId): JsonResponse
    {
        $requestUser = $this->em->getRepository(User::class)->find($userId);

        // check user 
        if(!$requestUser){
            return new JsonResponse([
                'statusCode' => 404,
                'status' => 'NOT_FOUND',
                'message' => "The requested user is not found"
            ], 400);
        }

        $this->denyAccessUnlessGranted('DELETE_USER', $requestUser);

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
