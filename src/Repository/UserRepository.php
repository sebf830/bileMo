<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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
    
    public function countApiUsers(array $params = null)
    {
        return  $this->createQueryBuilder('u')
           ->andWhere('u.client IS NOT NULL')
           ->getQuery()
           ->getArrayResult();
    }

   public function getApiUsers(array $params = null): array
   {
       $qb = $this->createQueryBuilder('u')
           ->leftJoin('u.client', 'c')
           ->where('c.id IS NOT NULL')
           ->andWhere('u.client IS NOT NULL')
           ->orderBy('u.id', 'DESC');

        if (isset($params['offset']) && $params['offset'] != null) {
            $qb->setFirstResult($params['offset']);
        }

        if (isset($params['per_page']) && $params['per_page'] != null) {
            $qb->setMaxResults($params['per_page']);
        }

        if($params['user']){
            $qb
            ->andWhere('u.id = :user')
            ->setParameter('user', $params['user']);
        }

        return $qb
           ->getQuery()
           ->getArrayResult();
   }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
