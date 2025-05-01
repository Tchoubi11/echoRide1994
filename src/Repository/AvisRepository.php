<?php

namespace App\Repository;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Récupère les avis liés à un utilisateur (en tant que passager ou conducteur)
     */
    public function findByUtilisateur(Utilisateur $utilisateur): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.reservation', 'r')
            ->join('r.covoiturage', 'c')
            ->where('r.passenger = :user OR c.driver = :user')
            ->setParameter('user', $utilisateur)
            ->getQuery()
            ->getResult();
    }
}
