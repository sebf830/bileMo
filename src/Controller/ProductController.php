<?php

namespace App\Controller;

use App\Entity\Products;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/products')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    #[Route('/', name: 'app_products_collection', methods: ['GET'])]
    public function getCollection(Request $request): JsonResponse
    {
        $params['page'] = (int)$request->get('page') != 0 ?  (int)$request->get('page') : 1;
        $params['per_page'] = (int)$request->get('per_page') != 0 ? (int)$request->get('per_page') : 5;
        $params['offset'] = $params['per_page'] * ($params['page'] - 1);

        $products = $this->em->getRepository(Products::class)->getApiProducts($params);
        $productsCount = $this->em->getRepository(Products::class)->countApiProducts();
    
        $totalPage = $params['per_page'] != null  
        ? ceil(count($productsCount) / $params['per_page']) 
        : ceil(count($productsCount) / 5);

        return new JsonResponse([
            'statusCode' => 200,
            'status' => 'SUCCESS',
            'currentPage' => $params['page'] != null ? $params['page'] : 1,
            'itemsPerPage' => $params['per_page'] != null ? $params['per_page']: 5,
            'count_items' => count($productsCount),
            'count_pages' => $totalPage,
            'data' => $products
        ], 200);
    }
}
