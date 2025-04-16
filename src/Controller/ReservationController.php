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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Psr\Log\LoggerInterface;
use App\Repository\ReservationRepository;
use Symfony\Bundle\SecurityBundle\Security;  // Importation de la bonne classe
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Form\UtilisateurType;
use App\Entity\Voiture;
use App\Form\VoitureType;


class ReservationController extends AbstractController
{
   
    #[Route('/participer/{rideId}', name: 'participer_covoiturage', requirements: ['rideId' => '\d+'], methods: ['GET', 'POST'])]
    public function participate(
        Request $request,
        int $rideId,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        LoggerInterface $logger
    ): Response {
        $logger->info("Tentative de participation au covoiturage avec rideId : " . $rideId);
    
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            $session->set('redirect_after_login', $request->getUri());
            $this->addFlash('error', 'Vous devez être connecté pour participer à un covoiturage.');
            return $this->redirectToRoute('app_login');
        }
    
        $ride = $entityManager->getRepository(Covoiturage::class)->find($rideId);
        if (!$ride) {
            $this->addFlash('error', 'Covoiturage non trouvé.');
            return $this->redirectToRoute('covoiturage_list');
        }
    
        // je vérifie si l'utilisateur a déjà une réservation
        $existingReservation = $entityManager->getRepository(Reservation::class)->findOneBy([
            'passenger' => $user,
            'covoiturage' => $ride,
        ]);
    
        if ($existingReservation) {
            $this->addFlash('error', 'Vous avez déjà une réservation pour ce covoiturage.');
            return $this->redirectToRoute('covoiturage_list');
        }
    
        // je vérifie les crédits et les places restantes
        if ($user->getCredits() < $ride->getPrixPersonne() || $ride->getPlacesRestantes() <= 0) {
            $this->addFlash('error', 'Vous n\'avez pas assez de crédits pour réserver.');
            return $this->render('reservation/participate.html.twig', [
                'ride' => $ride,
                'user' => $user,
                'rideId' => $rideId,
            ]);
        }
    
        // Gestion de la double confirmation
        if ($request->isMethod('POST')) {
            if ($session->get('confirm_'.$rideId)) {
                // Étape finale : on enregistre la réservation
                $reservation = new Reservation();
                $reservation->setPassenger($user);
                $reservation->setCovoiturage($ride);
                $reservation->setPlacesReservees(1);
                $reservation->setStatut('confirmée');
                $reservation->setMontantPaye($ride->getPrixPersonne());
    
                // je mets à jour les crédits et les places
                $user->setCredits($user->getCredits() - $ride->getPrixPersonne());
                $ride->setNbPlace($ride->getNbPlace() - 1);
    
                // j'associe explicitement le passager au trajet
                $ride->addPassenger($user);
    
                $entityManager->persist($reservation);
                $entityManager->flush();
    
                // je supprime la confirmation après l'enregistrement
                $session->remove('confirm_'.$rideId);
    
                $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');
                return $this->redirectToRoute('covoiturage_list');
            } else {
                // Première confirmation : Stocker dans la session
                $session->set('confirm_'.$rideId, true);
                $this->addFlash('warning', 'Veuillez confirmer votre réservation en cliquant à nouveau sur "Oui, confirmer".');
            }
        }
    
        return $this->render('reservation/participate.html.twig', [
            'ride' => $ride,
            'user' => $user,
            'rideId' => $rideId,
        ]);
    }
    

    //Annulation d’une réservation par un passager 
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


    #[Route('/mes-reservations', name: 'mes_reservations')]
    public function mesReservations(
        ReservationRepository $reservationRepository,
        Security $security,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $user = $security->getUser();
    
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
    
        $formTypeUtilisateur = null;
        $formVehicule = null;
        $covoituragesProposes = []; // ✅ initialisation ici
    
        // === Formulaire de rôle utilisateur ===
        $formTypeUtilisateur = $this->createForm(UtilisateurType::class, $user);
        $formTypeUtilisateur->handleRequest($request);
    
        if ($formTypeUtilisateur->isSubmitted() && $formTypeUtilisateur->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre rôle a bien été mis à jour.');
            return $this->redirectToRoute('mes_reservations');
        }
    
        // === Formulaire d'ajout de véhicule ===
        if (in_array(strtolower($user->getTypeUtilisateur()), ['chauffeur', 'les_deux'])) {
            $voiture = new Voiture();
            $voiture->setUtilisateur($user);
    
            $formVehicule = $this->createForm(VoitureType::class, $voiture);
            $formVehicule->handleRequest($request);
    
            if ($formVehicule->isSubmitted() && $formVehicule->isValid()) {
                $em->persist($voiture);
                $em->flush();
    
                $this->addFlash('success', 'Véhicule ajouté avec succès.');
                return $this->redirectToRoute('mes_reservations');
            }
    
            // ✅ On récupère ici les covoiturages proposés (si utilisateur est chauffeur ou les deux)
            $covoituragesProposes = $em->getRepository(\App\Entity\Covoiturage::class)->findBy([
                'driver' => $user,
            ]);
        }
    
        // === Récupération des réservations ===
        $reservations = $reservationRepository->findBy(['passenger' => $user]);
    
        return $this->render('utilisateur/espace_utilisateur.html.twig', [
            'utilisateur' => $user,
            'formType' => $formTypeUtilisateur?->createView(),
            'form' => $formVehicule ? $formVehicule->createView() : null,
            'reservations' => $reservations,
            'covoituragesProposes' => $covoituragesProposes,
        ]);
    }
    

}
