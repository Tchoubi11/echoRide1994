<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Covoiturage;
use App\Entity\Utilisateur;
use App\Entity\Voiture;
use App\Form\UtilisateurType;
use App\Form\VoitureType;
use App\Repository\ReservationRepository;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
   #[Route('/participer/{rideId}', name: 'participer_covoiturage', requirements: ['rideId' => '\d+'], methods: ['GET', 'POST'])]
public function participate(
    Request $request,
    int $rideId,
    EntityManagerInterface $entityManager,
    SessionInterface $session,
    LoggerInterface $logger,
    CreditService $creditService
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

    $existingReservation = $entityManager->getRepository(Reservation::class)->findOneBy([
        'passenger' => $user,
        'covoiturage' => $ride,
    ]);

    if ($existingReservation) {
        $this->addFlash('error', 'Vous avez déjà une réservation pour ce covoiturage.');
        return $this->redirectToRoute('covoiturage_list');
    }

    // Prix total : prix du covoiturage + 2 crédits de commission
    $prixTotal = $ride->getPrixPersonne() + 2;

    // Récupère les crédits utilisateur via CreditService (MongoDB)
    $userCredits = $creditService->getUserCredits($user->getId());

    if ($userCredits < $prixTotal || $ride->getPlacesRestantes() <= 0) {
        $this->addFlash('error', 'Vous n\'avez pas assez de crédits pour réserver ou plus de places disponibles.');
        return $this->render('reservation/participate.html.twig', [
            'ride' => $ride,
            'rideId' => $rideId,
            'userCredits' => $userCredits,
        ]);
    }

    if ($request->isMethod('POST')) {
        if ($session->get('confirm_' . $rideId)) {
            // Déduction des crédits
            $success = $creditService->removeCredits($user->getId(), $prixTotal);
            if (!$success) {
                $this->addFlash('error', 'Erreur lors du traitement des crédits.');
                return $this->redirectToRoute('covoiturage_list');
            }

            // Réservation
            $reservation = new Reservation();
            $reservation->setPassenger($user);
            $reservation->setCovoiturage($ride);
            $reservation->setPlacesReservees(1);
            $reservation->setStatut('confirmée');
            $reservation->setMontantPaye($ride->getPrixPersonne());

            $ride->setNbPlace($ride->getNbPlace() - 1);
            $ride->addPassenger($user);

            $entityManager->persist($reservation);
            $entityManager->flush();

            $session->remove('confirm_' . $rideId);

            $this->addFlash('success', 'Votre réservation a été confirmée avec succès !');
            return $this->redirectToRoute('covoiturage_list');
        } else {
            // Première soumission (demande de confirmation)
            $session->set('confirm_' . $rideId, true);
            $this->addFlash('warning', 'Veuillez confirmer votre réservation en cliquant à nouveau sur "Oui, confirmer".');
        }
    }

    return $this->render('reservation/participate.html.twig', [
        'ride' => $ride,
        'rideId' => $rideId,
        'userCredits' => $userCredits,
    ]);
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

        $formTypeUtilisateur = $this->createForm(UtilisateurType::class, $user);
        $formTypeUtilisateur->handleRequest($request);

        if ($formTypeUtilisateur->isSubmitted() && $formTypeUtilisateur->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre rôle a bien été mis à jour.');
            return $this->redirectToRoute('mes_reservations');
        }

        $formVehicule = null;
        $covoituragesProposes = [];

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

            $covoituragesProposes = $em->getRepository(Covoiturage::class)->findBy([
                'driver' => $user,
            ]);
        }

        $reservations = $reservationRepository->findBy(['passenger' => $user]);

        return $this->render('utilisateur/espace_utilisateur.html.twig', [
            'utilisateur' => $user,
            'formType' => $formTypeUtilisateur?->createView(),
            'form' => $formVehicule ? $formVehicule->createView() : null,
            'reservations' => $reservations,
            'covoituragesProposes' => $covoituragesProposes,
        ]);
    }

    #[Route('/reservation/{id}/valider', name: 'reservation_valider')]
public function validerReservation(
    Reservation $reservation,
    EntityManagerInterface $em,
    CreditService $creditService 
): Response {
    if ($reservation->getPassenger() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    if (!$reservation->getAParticipe()) {
        $this->addFlash('warning', 'Vous ne pouvez valider que si le chauffeur vous a marqué présent.');
        return $this->redirectToRoute('mes_reservations');
    }

    // Marque la participation du passager comme confirmée
    $reservation->setAConfirmeParticipation(true);

    // Vérification si le conducteur mérite des crédits 
    $covoiturage = $reservation->getCovoiturage();
    $driver = $covoiturage->getDriver();

    // Ajout des crédits au conducteur 
    if ($covoiturage->isCompleted() && $covoiturage->getPrixPersonne()) {  
    $amount = $covoiturage->getPrixPersonne();
    $creditService->addCredits($driver->getId(), $amount); 
    }


    // Enregistrement des changements
    $em->flush();

    $this->addFlash('success', 'Merci d\'avoir validé votre trajet !');
    return $this->redirectToRoute('mes_reservations');
}

}
