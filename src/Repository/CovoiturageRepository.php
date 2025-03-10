<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CovoiturageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covoiturage::class);
    }

    public function findAvailableRides(string $departure, string $destination, \DateTime $date)
{
    return $this->createQueryBuilder('c')
        ->andWhere('c.lieu_depart = :departure')
        ->andWhere('c.lieu_arrivee = :destination')
        ->andWhere('c.date_depart >= :date')
        ->andWhere('c.nb_place > 0')
        ->setParameter('departure', $departure)
        ->setParameter('destination', $destination)
        ->setParameter('date', $date)
        ->getQuery()
        ->getResult();
}

    public function findNextAvailableRide(string $departure, string $destination, \DateTime $date)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.lieu_depart = :departure')
            ->andWhere('c.lieu_arrivee = :destination')
            ->andWhere('c.date_depart > :date')
            ->andWhere('c.nb_place > 0')
            ->setParameter('departure', $departure)
            ->setParameter('destination', $destination)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
