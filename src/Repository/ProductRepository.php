<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countApiProducts(array $params = null)
    {
        return  $this->createQueryBuilder('p')
           ->getQuery()
           ->getArrayResult();
    }

    public function getApiProducts(array $params = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.name', 'DESC');

        if (!empty($params['embed'])) {
            $this->addSelected($this, $qb, $params['embed']);
        }
 
         if (isset($params['offset']) && $params['offset'] != null) {
             $qb->setFirstResult($params['offset']);
         }
 
         if (isset($params['per_page']) && $params['per_page'] != null) {
             $qb->setMaxResults($params['per_page']);
         }
 
         if(isset($params['product'])){
             $qb
             ->andWhere('p.id = :product')
             ->setParameter('product', $params['product']);
         }
 
         return $qb
            ->getQuery()
            ->getArrayResult();
    }

    private function addSelected($entityClass, QueryBuilder $qb, $params):void 
    {
        $relationList = [];

        // get the relation of entity (strings)
        foreach(get_object_vars($entityClass)["_class"]->getAssociationMappings() as $property){
            $relationList[] = $property['fieldName'];
        }
        
        // get the alias letter of the entity
        $alias = $qb->getDQLParts()['from'][0]->getAlias();

        if(is_array($params) && count($params) > 0){
            for($i = 0; $i < count($params); $i++){
                // if the relation exists 
                if(in_array($params[$i], $relationList)){
                    $qb
                    ->leftJoin("{$alias}.{$params[$i]}", "o{$i}")
                    ->addSelect("o{$i}")
                    ;
                }
            }
        }
    }
}
