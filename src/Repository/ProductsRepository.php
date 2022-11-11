<?php

namespace App\Repository;

use App\Entity\Products;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Products>
 *
 * @method Products|null find($id, $lockMode = null, $lockVersion = null)
 * @method Products|null findOneBy(array $criteria, array $orderBy = null)
 * @method Products[]    findAll()
 * @method Products[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Products::class);
    }

    public function save(Products $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Products $entity, bool $flush = false): void
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
}
