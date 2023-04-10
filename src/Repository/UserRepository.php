<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }


   public function getApiUsers(array $params = null): array
   {
       $qb = $this->createQueryBuilder('u')
           ->leftJoin('u.parent', 'p')
           ->where('p.id IS NOT NULL')
           ->orderBy('u.id', 'DESC');

        if(isset($params['embed']) && !is_null($params['embed'])){
            $this->addSelected($this, $qb, $params['embed']);
        }

        if (isset($params['offset']) && !is_null($params['offset'])) {
            $qb->setFirstResult($params['offset']);
        }

        if (isset($params['per_page']) && !is_null($params['per_page'])) {
            $qb->setMaxResults($params['per_page']);
        }

        if(isset($params['clientUser']) && !is_null($params['clientUser'])){
            $qb
            ->andWhere('u.id = :clientUser')
            ->setParameter('clientUser', $params['clientUser']);
        }

        if(isset($params['client']) && !is_null($params['client'])){
            $qb
            ->andWhere('p.id = :parent')
            ->setParameter('parent', $params['client']);
        }

        return $qb
           ->getQuery()
           ->getArrayResult();
   }

   public function getUser(array $params): array
   {
        $qb = $this->createQueryBuilder('u')
        ->where('u.id = :id')
        ->setParameter('id', $params['user'])
        ->orderBy('u.id', 'DESC');

        if(isset($params['embed']) && !is_null($params['embed'])){
            $this->addSelected($this, $qb, $params['embed']);
        }

        return $qb->getQuery()->getArrayResult();
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
