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

    /**
     * Trouver des covoiturages en fonction des critères de recherche
     *
     * @param string $departure
     * @param string $destination
     * @param \DateTime $date
     * @return Covoiturage[]
     */
    public function findByCriteria(string $departure, string $destination, \DateTime $date): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.departure = :departure')
            ->andWhere('c.destination = :destination')
            ->andWhere('c.departureTime >= :date')
            ->setParameter('departure', $departure)
            ->setParameter('destination', $destination)
            ->setParameter('date', $date->format('Y-m-d H:i:s'));

        return $qb->getQuery()->getResult();
    }
}
