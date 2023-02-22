<?php

namespace App\Controller;

use App\Entity\Product;
use OpenApi\Attributes as OA;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;

#[OA\Tag(name: 'products')]
#[Route('/api/products')]
class ProductController extends AbstractController
{
    private EntityManagerInterface $em;
    private CacheInterface $cache;

    public function __construct(EntityManagerInterface $em, CacheInterface $cache, JWTEncoderInterface $encoder) {
        $this->em = $em;
        $this->cache = $cache;
        $this->encoder = $encoder;
    }

    public static function links(){
        return [
            ["name" => "getCollection", "type" => "collection", "verb" => "GET", "href" => '/products'],
            ["name" => "getItem", "type" => "item","verb" => "GET", "href" => '/products']
        ];
    }

    /**
     *  Allow a client to access product list and details
     */
    #[OA\Response(
        response: 200,
        description: 'returns a collection of products',
    )]
    #[OA\Parameter(
        name: 'embed',
        in: 'query',
        description: 'allow user to get a relation datas (?embed=category)',
        schema: new OA\Schema(type: 'string')
    )]
    #[Route('/', name: 'app_products_collection', methods: ['GET'])]
    public function getCollection(Request $request, ProductRepository $productRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW_PRODUCT');

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
            'datas' => $products
        ], 200);
    }


    /**
     *  Allow a client to access a product details
     */
    #[OA\Response(
        response: 200,
        description: 'Returns a product',
    )]
    #[OA\Parameter(
        name: 'embed',
        in: 'query',
        description: 'allow user to get a relation datas (?embed=category)',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'productId',
        in: 'path',
        description: 'product id',
        schema: new OA\Schema(type: 'integer')
    )]
    #[Route('/{productId}', name: 'app_products_item', methods: ['GET'])]
    public function getItem(Request $request, int $productId, ProductRepository $productRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW_PRODUCT');

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
            'datas' => $product
        ], 200);
    }
}
