<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/users')]
class UserController extends AbstractController
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/', name: 'app_user', methods: ['GET'])]
    public function getCollection(Request $request): JsonResponse
    {
        $params['page'] = (int)$request->get('page') != 0 ?  (int)$request->get('page') : 1;
        $params['per_page'] = (int)$request->get('per_page') != 0 ? (int)$request->get('per_page') : 5;
        $params['offset'] = $params['per_page'] * ($params['page'] - 1);

        $users = $this->em->getRepository(User::class)->getUserCollection($params);
        $usersCount = $this->em->getRepository(User::class)->countUserCollection();
    
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
}
