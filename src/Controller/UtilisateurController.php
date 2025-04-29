<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Image;
use App\Entity\Voiture;
use App\Form\VoitureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\UtilisateurType;
use App\Entity\Reservation;
use App\Entity\Covoiturage;
use App\Form\CovoiturageType;
use App\Service\NotificationService;
use Symfony\Bundle\SecurityBundle\Security; 
use App\Form\ReservationValidationType;
use App\Entity\Avis; 

class UtilisateurController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }

        if (!$utilisateur->getPhoto()) {
            $defaultPhoto = new Image();
            $defaultPhoto->setImagePath('default-avatar.png');
            $utilisateur->setPhoto($defaultPhoto);

            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();
        }

        return $this->render('utilisateur/profile.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/espace-utilisateur', name: 'espace_utilisateur')]
    public function espaceUtilisateur(Request $request): Response
    {
        $user = $this->getUser();
    
        if (!$user instanceof Utilisateur) {
            return $this->redirectToRoute('app_login');
        }
    
        $formTypeUtilisateur = $this->createForm(UtilisateurType::class, $user);
        $formTypeUtilisateur->handleRequest($request);
    
        if ($formTypeUtilisateur->isSubmitted() && $formTypeUtilisateur->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Type d\'utilisateur mis à jour.');
            return $this->redirectToRoute('espace_utilisateur');
        }
    
        $formVehicule = null;
        $accepteFumeur = false;
    
        if (in_array(strtolower($user->getTypeUtilisateur()), ['chauffeur', 'les_deux'])) {
            $vehicule = new Voiture();
            $vehicule->setUtilisateur($user);
            $formVehicule = $this->createForm(VoitureType::class, $vehicule);
            $formVehicule->handleRequest($request);
    
            if ($formVehicule->isSubmitted() && $formVehicule->isValid()) {
                $this->entityManager->persist($vehicule);
                $this->entityManager->flush();
            }
    
            $preference = $vehicule->getPreference();
            if ($preference && $preference->isFumeur()) {
                $accepteFumeur = true;
            }
        }
    
        $reservations = $this->entityManager->getRepository(Reservation::class)->findBy([
            'passenger' => $user,
        ]);
    
        $covoituragesProposes = $this->entityManager->getRepository(Covoiturage::class)->findBy([
            'driver' => $user,
        ]);
    
        $formCovoiturage = null;
    
        if (in_array(strtolower($user->getTypeUtilisateur()), ['chauffeur', 'les_deux'])) {
            $covoiturage = new Covoiturage();
            $covoiturage->setDriver($user);
            $covoiturage->setStatut('disponible');

    
            $formCovoiturage = $this->createForm(CovoiturageType::class, $covoiturage, [
                'user' => $user,
            ]);
            $formCovoiturage->handleRequest($request);
    
            // Correction ici : calcul de la date d’arrivée après handleRequest
            if ($formCovoiturage->isSubmitted()) {
                dump($covoiturage->getNbPlace());
                if ($covoiturage->getDateDepart() && !$covoiturage->getDateArrivee()) {
                    $dateDepart = $covoiturage->getDateDepart();
                    $dateArrivee = new \DateTime($dateDepart->format('Y-m-d H:i:s'));
                    $dateArrivee->modify('+2 hours'); 
    
                    $covoiturage->setDateArrivee($dateArrivee);
                }
    
                if ($formCovoiturage->isValid()) {
                    $this->entityManager->persist($covoiturage);
                    $this->entityManager->flush();
    
                    $this->addFlash('success', 'Trajet enregistré avec succès.');
                    return $this->redirectToRoute('espace_utilisateur');
                }
            }
        }
    
        return $this->render('utilisateur/espace_utilisateur.html.twig', [
            'utilisateur' => $user,
            'formType' => $formTypeUtilisateur->createView(),
            'form' => $formVehicule ? $formVehicule->createView() : null,
            'formCovoiturage' => $formCovoiturage ? $formCovoiturage->createView() : null,
            'accepteFumeur' => $accepteFumeur,
            'reservations' => $reservations,
            'covoituragesProposes' => $covoituragesProposes,
        ]);
    }
    
    #[Route('/historique-covoiturages', name: 'historique_covoiturages')]
    public function historiqueCovoiturages(): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $enTantQuePassager = $this->entityManager->getRepository(Reservation::class)->findBy([
            'passenger' => $user,
        ]);

        $enTantQueConducteur = $this->entityManager->getRepository(Covoiturage::class)->findBy([
            'driver' => $user,
        ]);

        return $this->render('utilisateur/historique.html.twig', [
            'reservations' => $enTantQuePassager,
            'covoiturages' => $enTantQueConducteur,
        ]);
    }

    
    #[Route('/mon-espace/covoiturages-a-valider', name: 'reservations_to_validate')]
public function validateReservations(
    Request $request,
    EntityManagerInterface $em,
    NotificationService $notificationService,
    Security $security
): Response {
    $user = $security->getUser();

    // On récupère uniquement les réservations qui ne sont pas encore validées
    $reservations = $em->getRepository(Reservation::class)->findBy([
        'passenger' => $user,
        'isValidatedByPassenger' => null,
    ]);

    $forms = [];

    foreach ($reservations as $reservation) {
        $form = $this->createForm(ReservationValidationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setIsValidatedByPassenger(true); // Validation du passager
        
            // Mettre à jour les feedback et notes du passager
            $reservation->setPassengerFeedback($form->get('passengerFeedback')->getData());
            $reservation->setPassengerNote($form->get('passengerNote')->getData());
        
            // Gestion des crédits pour le conducteur
            $driver = $reservation->getCovoiturage()->getDriver();
            $driver->setCredits($driver->getCredits() + $reservation->getCovoiturage()->getPrixPersonne());
        
            // Notification au conducteur que le passager a validé
            $notificationService->notifyDriverOfValidation($driver, $reservation);
        
            // Enregistrement des avis
            if ($reservation->getPassengerNote() || $reservation->getPassengerFeedback()) {
                $avis = new Avis();
                $avis->setUser($user);
                $avis->setNote($reservation->getPassengerNote());
                $avis->setCommentaire($reservation->getPassengerFeedback());
                $avis->setStatut('en attente');
                $em->persist($avis);
            }
        
            // Ajouter ici la mise à jour du champ `isCompleted`
            $reservation->setIsCompleted(true); // Marquer le trajet comme terminé pour le conducteur
        
            // Sauvegarde en base de données
            $em->flush();
        
            // Redirection ou message de confirmation
            return $this->redirectToRoute('reservations_to_validate');
        }
        

        $forms[$reservation->getId()] = $form->createView();
    }

    return $this->render('utilisateur/reservations_to_validate.html.twig', [
        'participations' => $reservations,
        'participationForms' => $forms,
    ]);
}


#[Route('/reservation/{id}/signaler-probleme', name: 'reservation_signaler_probleme', methods: ['POST'])]
public function signalerProbleme(int $id, Request $request, EntityManagerInterface $em, NotificationService $notificationService): Response
{
    // Récupérer la réservation
    $reservation = $em->getRepository(Reservation::class)->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('Réservation introuvable.');
    }

    // Créer le formulaire de signalement de problème
    $form = $this->createForm(ReservationValidationType::class, $reservation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // 🚨 Mettre à jour les champs de feedback et de note
        $reservation->setPassengerFeedback($form->get('passengerFeedback')->getData());
        $reservation->setPassengerNote((int) $form->get('passengerNote')->getData()); // Forcer la conversion en entier

        // Signaler que le problème est rapporté
        $reservation->setIssueReported(true);

        // Sauvegarde en base de données
        $em->flush();

        // Optionnel : Ajouter une notification au conducteur (si nécessaire)
        // $notificationService->notifyDriverOfProblem($driver, $reservation);

        // Message flash pour l'utilisateur
        $this->addFlash('success', 'Problème signalé avec succès.');

        // Redirection pour recharger la liste sans la réservation signalée
        return $this->redirectToRoute('reservations_to_validate');
    }

    // Si le formulaire n'est pas soumis ou invalide, on l'ajoute dans la vue
    return $this->render('utilisateur/signaler_probleme.html.twig', [
        'reservation' => $reservation,
        'form' => $form->createView(),
    ]);
}

#[Route('/mon-espace/covoiturage/{id}/terminer', name: 'driver_terminate_trip')]
public function terminateTrip(
    int $id,
    EntityManagerInterface $em,
    NotificationService $notificationService
): Response {
    // On récupère le covoiturage
    $covoiturage = $em->getRepository(Covoiturage::class)->find($id);

    if (!$covoiturage) {
        throw $this->createNotFoundException('Covoiturage introuvable.');
    }

    // Pour chaque réservation associée au covoiturage
    foreach ($covoiturage->getReservations() as $reservation) {
        // Marquer que le conducteur a terminé le trajet
        $reservation->setIsCompleted(true); // ou setIsDriverCompleted(true) si tu veux différencier

        // Envoyer notification au passager
        $notificationService->notifyPassengerToValidate($reservation);
    }

    // Optionnel : marquer aussi le covoiturage comme terminé
    $covoiturage->setIsCompleted(true);

    $em->flush();

    $this->addFlash('success', 'Trajet terminé, les passagers sont informés.');

    return $this->redirectToRoute('espace_utilisateur');
}


   
}
