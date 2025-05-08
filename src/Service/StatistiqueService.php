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
        $conn = $this->em->getConnection();
        $sql = "
            SELECT DATE(c.start_at) as jour, COUNT(DISTINCT c.id) as nb
            FROM covoiturage c
            INNER JOIN reservation r ON c.id = r.covoiturage_id
            WHERE r.statut = :statut
            AND c.start_at IS NOT NULL
            GROUP BY jour
            ORDER BY jour ASC
        ";
    
        $stmt = $conn->prepare($sql);
$stmt->bindValue('statut', 'confirmée');
$resultSet = $stmt->executeQuery();

    
        return array_column($resultSet->fetchAllAssociative(), 'nb', 'jour');
    }
    


public function getCreditsParJour(): array
{
    // On récupère toutes les réservations validées
    $reservations = $this->em->getRepository(\App\Entity\Reservation::class)->findBy([
        'statut' => 'confirmée'
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
