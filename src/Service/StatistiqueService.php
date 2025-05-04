<?php 

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class StatistiqueService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getCovoituragesParJour(): array
    {
        $result = $this->em->createQuery(
            'SELECT DATE(r.date) as jour, COUNT(r.id) as total
             FROM App\Entity\Reservation r
             WHERE r.status = :status
             GROUP BY jour
             ORDER BY jour ASC'
        )->setParameter('status', 'validée')
         ->getResult();

        return array_column($result, 'total', 'jour');
    }

    public function getCreditsParJour(): array
    {
        $result = $this->em->createQuery(
            'SELECT DATE(r.date) as jour, SUM(r.credits) as total
             FROM App\Entity\Reservation r
             WHERE r.status = :status
             GROUP BY jour
             ORDER BY jour ASC'
        )->setParameter('status', 'validée')
         ->getResult();

        return array_column($result, 'total', 'jour');
    }

    public function getTotalCredits(): float
    {
        return (float) $this->em->createQuery(
            'SELECT SUM(r.credits) FROM App\Entity\Reservation r WHERE r.status = :status'
        )->setParameter('status', 'validée')
         ->getSingleScalarResult();
    }
}
