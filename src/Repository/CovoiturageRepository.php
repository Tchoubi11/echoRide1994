<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class CovoiturageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covoiturage::class);
    }

    // Trouver les covoiturages disponibles en fonction des critères
    public function findAvailableRides(string $departure, string $destination, \DateTime $date): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.departureAddress LIKE :departure')
            ->andWhere('c.arrivalAddress LIKE :destination')
            ->andWhere('c.departureTime >= :date')
            ->andWhere('c.availableSeats > 0')
            ->setParameter('departure', '%' . $departure . '%')
            ->setParameter('destination', '%' . $destination . '%')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    // Trouver le trajet suivant disponible pour les mêmes critères (si aucun trajet n'est trouvé pour la date donnée)
    public function findNextAvailableRide(string $departure, string $destination, \DateTime $date): ?Covoiturage
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.departureAddress LIKE :departure')
            ->andWhere('c.arrivalAddress LIKE :destination')
            ->andWhere('c.departureTime > :date')
            ->orderBy('c.departureTime', 'ASC')
            ->setParameter('departure', '%' . $departure . '%')
            ->setParameter('destination', '%' . $destination . '%')
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
