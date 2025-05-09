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

    public function findAvailableRides(string $depart, string $arrivee, \DateTimeInterface $date): array
    {
        $mutableDate = \DateTime::createFromInterface($date);
    
        $startOfDay = (clone $mutableDate)->setTime(0, 0);
        $endOfDay = (clone $mutableDate)->setTime(23, 59, 59);
    
        return $this->createQueryBuilder('c')
            ->andWhere('c.lieu_depart = :depart')
            ->andWhere('c.lieu_arrivee = :arrivee')
            ->andWhere('c.heure_depart >= :start') 
            ->andWhere('c.isCancelled = false')
            ->setParameter('depart', $depart)
            ->setParameter('arrivee', $arrivee)
            ->setParameter('start', $startOfDay) 
            ->getQuery()
            ->getResult();
    }
    


public function findNextAvailableRide(string $departure, string $destination, \DateTime $date)
{
    return $this->createQueryBuilder('c')
        ->andWhere('c.lieu_depart = :departure')
        ->andWhere('c.lieu_arrivee = :destination')
        ->andWhere('c.heure_depart > :date') 
        ->andWhere('c.nbPlace > 0')
        ->setParameter('departure', $departure)
        ->setParameter('destination', $destination)
        ->setParameter('date', $date)
        ->getQuery()
        ->getResult();
}

    public function findWithReservations(int $id): ?Covoiturage
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.reservations', 'r')->addSelect('r')
            ->leftJoin('r.passenger', 'p')->addSelect('p')
            ->leftJoin('c.driver', 'd')->addSelect('d')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    


    
public function findFilteredRides(
    string $departure, 
    string $destination, 
    ?\DateTime $dateDepart, 
    ?string $lieuDepart, 
    ?string $lieuArrivee, 
    ?float $maxPrice, 
    ?int $maxDuration, 
    ?int $minRating, 
    ?bool $isEco
) {
    $queryBuilder = $this->createQueryBuilder('c')
        ->andWhere('c.lieu_depart = :departure')
        ->andWhere('c.lieu_arrivee = :destination')
        ->setParameter('departure', $departure)
        ->setParameter('destination', $destination);

        if ($dateDepart) {
            $queryBuilder
                ->andWhere('c.heure_depart >= :date')
                ->setParameter('date', $dateDepart);
        }

    if ($lieuDepart) {
        $queryBuilder->andWhere('c.lieu_depart LIKE :lieuDepart')
            ->setParameter('lieuDepart', '%' . $lieuDepart . '%');
    }

    if ($lieuArrivee) {
        $queryBuilder->andWhere('c.lieu_arrivee LIKE :lieuArrivee')
            ->setParameter('lieuArrivee', '%' . $lieuArrivee . '%');
    }

    if ($maxPrice !== null) {
        $queryBuilder->andWhere('c.prix_personne <= :maxPrice')
            ->setParameter('maxPrice', $maxPrice);
    }

    if ($maxDuration !== null) {
        $queryBuilder->andWhere('c.max_duration <= :maxDuration')
            ->setParameter('maxDuration', $maxDuration);
    }    

    if ($minRating !== null) {
        $queryBuilder->andWhere('c.min_rating >= :minRating')
            ->setParameter('minRating', $minRating);
    }
    

    if ($isEco !== null) {
        $queryBuilder->andWhere('c.isEco = :isEco')
            ->setParameter('isEco', $isEco);
    }

    return $queryBuilder->getQuery()->getResult();
}

    
}
