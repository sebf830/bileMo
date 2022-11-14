<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use JMS\Serializer\Serializer;

#[Route('/api/products')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $em;
    private CacheInterface $cache;

    public function __construct(EntityManagerInterface $em, CacheInterface $cache) {
        $this->em = $em;
        $this->cache = $cache;
    }

    public static function links(){
        return [
            ["name" => "getCollection", "type" => "collection", "verb" => "GET", "href" => '/products'],
            ["name" => "getItem", "type" => "item","verb" => "GET", "href" => '/products']
        ];
    }

    #[Route('/', name: 'app_products_collection', methods: ['GET'])]
    public function getCollection(Request $request, ProductRepository $productRepo): JsonResponse
    {
        $params['page'] = (int)$request->get('page') != 0 ?  (int)$request->get('page') : 1;
        $params['per_page'] = (int)$request->get('per_page') != 0 ? (int)$request->get('per_page') : 5;
        $params['offset'] = $params['per_page'] * ($params['page'] - 1);
        $params['embed'] = $request->get('embed') ? $request->get('embed') : [];


        $productsCount = $this->em->getRepository(Product::class)->countApiProducts();

        $cacheName = 'getProducts-' . $params['page'] . '-'. $params['per_page'] .'-'. implode('-', $params['embed']) ;

        $products = $this->cache->get($cacheName, function(ItemInterface $item) use($productRepo, $params){
            $item->expiresAfter(3600);
            return $productRepo->getApiProducts($params);
        });
    
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

    #[Route('/{productId}', name: 'app_products_item', methods: ['GET'])]
    public function getItem(Request $request, int $productId, ProductRepository $productRepo): JsonResponse
    {
        if(!$productId || $productId == null || intval($productId) < 1){
            return new JsonResponse([
                'statusCode' => 400,
                'status' => 'BAD_REQUEST',
                'message' => "missing or incorrect parameter id"
            ], 400);
        }

        $params['product'] = $productId;
        $params['embed'] = $request->get('embed') ? $request->get('embed') : [];

        $cacheName = 'getProduct' . $productId .'-'. implode('-', $params['embed']);

        $product = $this->cache->get($cacheName, function(ItemInterface $item) use($productRepo, $params){
            $item->expiresAfter(3600);
            return $productRepo->getApiProducts($params);
        });

        if(!$product){
            return new JsonResponse([
                'statusCode' => 404,
                'status' => 'USER_NOT_FOUND',
                'message' => "the request is not found"
            ], 404);
        }

        return new JsonResponse([
            'statusCode' => 200,
            'status' => 'SUCCESS',
            'data' => $product
        ], 200);
    }
}
