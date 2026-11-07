<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Trajet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findActiveForPassengerAndTrajet(User $passager, Trajet $trajet): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.passager = :passager')
            ->andWhere('r.trajet = :trajet')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('passager', $passager)
            ->setParameter('trajet', $trajet)
            ->setParameter('statuts', ['EN_ATTENTE', 'CONFIRMEE'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
