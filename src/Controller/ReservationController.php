<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Covoiturage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/reservation')]
class ReservationController extends AbstractController
{
    #[Route('/covoiturage/{id}/book', name: 'reservation_book')]
    public function book(Covoiturage $covoiturage, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Vérifions s'il reste des places disponibles
        if ($covoiturage->getPlacesRestantes() <= 0) {
            $this->addFlash('danger', 'Plus de places disponibles.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
        }

        // Vérifions si l'utilisateur a déjà réservé
        foreach ($covoiturage->getReservations() as $reservation) {
            if ($reservation->getPassenger() === $user) {
                $this->addFlash('danger', 'Vous avez déjà une réservation.');
                return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
            }
        }

        // Créons la nouvelle réservation
        $reservation = new Reservation();
        $reservation->setPassenger($user);
        $reservation->setCovoiturage($covoiturage);
        $reservation->setPlacesReservees(1);
        $reservation->setStatut('confirmée');

        $em->persist($reservation);
        $em->flush();

        $this->addFlash('success', 'Réservation confirmée !');
        return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
    }

    #[Route('/{id}/cancel', name: 'reservation_cancel')]
    public function cancel(Reservation $reservation, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Vérifier si l'utilisateur est bien le passager
        if ($reservation->getPassenger() !== $user) {
            throw $this->createAccessDeniedException('Action non autorisée.');
        }

        // Annulons la réservation
        $reservation->setStatut('annulée');
        $em->flush();

        $this->addFlash('success', 'Votre réservation a été annulée.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $reservation->getCovoiturage()->getId()]);
    }
}
