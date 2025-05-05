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
    // On récupère toutes les réservations validées
    $reservations = $this->em->getRepository(\App\Entity\Reservation::class)->findBy([
        'statut' => 'validée'
    ]);

    // On groupe les covoiturages par jour
    $groupedByDay = [];
    foreach ($reservations as $r) {
        // Accéder à la date de départ du covoiturage associé
        $date = $r->getCovoiturage()?->getDateDepart();

        if (!$date instanceof \DateTimeInterface) {
            continue; // On ignore si la date est invalide
        }

        // On formatte la date pour la grouper par jour
        $day = $date->format('Y-m-d');
        if (!isset($groupedByDay[$day])) {
            $groupedByDay[$day] = 0;
        }
        $groupedByDay[$day]++;
    }

    // Trier par date
    ksort($groupedByDay);
    return $groupedByDay;
}


public function getCreditsParJour(): array
{
    // On récupère toutes les réservations validées
    $reservations = $this->em->getRepository(\App\Entity\Reservation::class)->findBy([
        'statut' => 'validée'
    ]);

    // On groupe les crédits par jour
    $creditsByDay = [];
    foreach ($reservations as $r) {
        // Accéder à la date de départ du covoiturage associé
        $date = $r->getCovoiturage()?->getDateDepart();
        if (!$date instanceof \DateTimeInterface) {
            continue; // On ignore si la date est invalide
        }

        // On formatte la date pour la grouper par jour
        $day = $date->format('Y-m-d');

        // Récupérer le montant payé par le passager
        $montant = $r->getMontantPaye() ?? 0;

        if (!isset($creditsByDay[$day])) {
            $creditsByDay[$day] = 0;
        }
        $creditsByDay[$day] += $montant;
    }

    // Trier par date
    ksort($creditsByDay);
    return $creditsByDay;
}


public function getTotalCredits(): float
{
    return (float) $this->em->createQuery(
        'SELECT SUM(r.montantPaye) FROM App\Entity\Reservation r WHERE r.statut = :status'
    )->setParameter('status', 'validée')
     ->getSingleScalarResult();
}

}
