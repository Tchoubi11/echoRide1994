<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Utilisateur;
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

    /**
     * Retourne les réservations actives (non annulées) d’un passager
     *
     * @param Utilisateur $passenger
     * @return Reservation[]
     */
    public function findActiveReservationsForPassenger(Utilisateur $passenger): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.passenger = :passenger')
            ->andWhere('r.isCancelled = false')
            ->andWhere('r.covoiturage IS NOT NULL')
            ->setParameter('passenger', $passenger)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les réservations annulées d’un passager
     *
     * @param Utilisateur $passenger
     * @return Reservation[]
     */
    public function findCancelledReservationsForPassenger(Utilisateur $passenger): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.passenger = :passenger')
            ->andWhere('r.isCancelled = true')
            ->setParameter('passenger', $passenger)
            ->getQuery()
            ->getResult();
    }
}
