<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Covoiturage;
use App\Entity\Utilisateur;  
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class ReservationController extends AbstractController
{
    #[Route('/reservation/covoiturage/{id}/book', name: 'reservation_book', methods: ['POST'])]
    public function book(Covoiturage $covoiturage, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // ici je vérifie si l'utilisateur est connecté et de type Utilisateur
        if (!$user || !$user instanceof Utilisateur) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non valide ou non connecté.']);
        }

        // je vérifie si l'utilisateur a suffisamment de crédits
        $creditCost = $request->get('credit_cost');
        if ($user->getCredits() < $creditCost) {
            return $this->json(['success' => false, 'message' => 'Crédits insuffisants.']);
        }

        // je vérifie s'il reste des places disponibles
        if ($covoiturage->getNbPlace() <= 0) {
            return $this->json(['success' => false, 'message' => 'Plus de places disponibles.']);
        }

        // Ici on crée la réservation
        $reservation = new Reservation();
        $reservation->setPassenger($user);
        $reservation->setCovoiturage($covoiturage);
        $reservation->setPlacesReservees(1);
        $reservation->setStatut('confirmée');

        // on déduit les crédits
        $user->setCredits($user->getCredits() - $creditCost);

        // Mettons à jour le nombre de places disponibles
        $covoiturage->setNbPlace($covoiturage->getNbPlace() - 1);

        // Sauvegardons les modifications en base de données
        $em->persist($reservation);
        $em->persist($user);
        $em->persist($covoiturage);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Réservation confirmée avec succès.']);
    }

    #[Route('/reservation/{id}/cancel', name: 'cancel_reservation', methods: ['POST'])]
    public function cancelReservation(int $id, EntityManagerInterface $em): JsonResponse
    {
        // ici on récupére la réservation par son ID
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Réservation introuvable.'], 404);
        }

        // on vérifie si l'utilisateur connecté est bien celui qui a effectué la réservation
        $user = $this->getUser();
        if ($reservation->getPassenger() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez annuler que vos propres réservations.'], 403);
        }

        // on récupére le covoiturage associé à la réservation
        $covoiturage = $reservation->getCovoiturage();

        // on reemett à jour le nombre de places disponibles
        $covoiturage->setNbPlace($covoiturage->getNbPlace() + $reservation->getPlacesReservees());

        // Supprimons la réservation
        $em->remove($reservation);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Réservation annulée avec succès.']);
    }
}
