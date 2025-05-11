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
        ->leftJoin('c.driver', 'd')  
        ->addSelect('d')             
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


public function searchWithEnoughPlaces(int $nbPeople, ?\DateTime $dateDepart = null, ?string $lieuDepart = null, ?string $lieuArrivee = null)
{
    $qb = $this->createQueryBuilder('c')
        ->where('c.nbPlace >= :nbPeople')
        ->setParameter('nbPeople', $nbPeople);

    // Filtre par date de départ si spécifié
    if ($dateDepart) {
        $qb->andWhere('c.heure_depart >= :dateDepart')
           ->setParameter('dateDepart', $dateDepart);
    }

    // Filtre par lieu de départ si spécifié
    if ($lieuDepart) {
        $qb->andWhere('LOWER(c.lieu_depart) LIKE :lieuDepart')
           ->setParameter('lieuDepart', '%' . strtolower($lieuDepart) . '%');
    }

    // Filtre par lieu d'arrivée si spécifié
    if ($lieuArrivee) {
        $qb->andWhere('LOWER(c.lieu_arrivee) LIKE :lieuArrivee')
           ->setParameter('lieuArrivee', '%' . strtolower($lieuArrivee) . '%');
    }

    return $qb->getQuery()->getResult();
}



    
//public function searchWithFilters(array $criteria): array
//{
 //   $qb = $this->createQueryBuilder('c')
    //    ->join('c.driver', 'd')
     //   ->where('c.dateDepart >= :now')
    //    ->setParameter('now', new \DateTime());

   // if (!empty($criteria['lieu_depart'])) {
   //     $qb->andWhere('LOWER(c.lieu_depart) LIKE :lieu_depart')
       //    ->setParameter('lieu_depart', '%' . strtolower($criteria['lieu_depart']) . '%');
  //  }

  //  if (!empty($criteria['lieu_arrivee'])) {
   //     $qb->andWhere('LOWER(c.lieu_arrivee) LIKE :lieu_arrivee')
   //        ->setParameter('lieu_arrivee', '%' . strtolower($criteria['lieu_arrivee']) . '%');
   // }

  //  if (!empty($criteria['date_depart'])) {
    //    $qb->andWhere('DATE(c.dateDepart) = :date_depart')
  //         ->setParameter('date_depart', $criteria['date_depart']->format('Y-m-d'));
  //  }

  //  if (!empty($criteria['max_price'])) {
   //     $qb->andWhere('c.prixPersonne <= :max_price')
        //   ->setParameter('max_price', $criteria['max_price']);
  //  }

  //  if (!empty($criteria['max_duration'])) {
   //     $qb->andWhere('c.duree <= :max_duration')
   //        ->setParameter('max_duration', $criteria['max_duration']);
  //  }

   // if (!empty($criteria['min_rating'])) {
   //     $qb->andWhere('d.rating >= :min_rating')
  //         ->setParameter('min_rating', $criteria['min_rating']);
  //  }

  //  if (!empty($criteria['is_eco'])) {
   //     $qb->andWhere('c.isEco = :is_eco')
    //       ->setParameter('is_eco', $criteria['is_eco']);
   // }

 //   return $qb->getQuery()->getResult();
//}

}
