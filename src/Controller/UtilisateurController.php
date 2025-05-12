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
use App\Repository\AvisRepository;
use App\Service\CreditService;


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

    if (in_array(strtolower($user->getTypeUtilisateur()), ['chauffeur', 'les_deux'])) {
        $vehicule = new Voiture();
        $vehicule->setUtilisateur($user);
        $formVehicule = $this->createForm(VoitureType::class, $vehicule);
        $formVehicule->handleRequest($request);

        if ($formVehicule->isSubmitted() && $formVehicule->isValid()) {
            $this->entityManager->persist($vehicule);
            $this->entityManager->flush();
        }
    }

    $reservations = $this->entityManager->getRepository(Reservation::class)->findBy([
        'passenger' => $user,
    ]);

    $covoituragesProposes = $this->entityManager->getRepository(Covoiturage::class)->findBy([
        'driver' => $user,
    ]);

    // ✅ Vérifie les préférences fumeur à partir des covoiturages existants
    $accepteFumeur = false;
    foreach ($covoituragesProposes as $covoiturage) {
        $preference = $covoiturage->getPreference();
        if ($preference && $preference->isFumeur()) {
            $accepteFumeur = true;
            break;
        }
    }

    $formCovoiturage = null;

    if (in_array(strtolower($user->getTypeUtilisateur()), ['chauffeur', 'les_deux'])) {
        $covoiturage = new Covoiturage();
        $covoiturage->setDriver($user);
        $covoiturage->setStatut('disponible');

        $formCovoiturage = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $user,
        ]);
        $formCovoiturage->handleRequest($request);

        if ($formCovoiturage->isSubmitted()) {
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
    Security $security,
    CreditService $creditService // Injection de CreditService
): Response {
    $user = $security->getUser();

    // Seules les réservations où le chauffeur et le passager ont confirmé leur participation
    $reservations = $em->getRepository(Reservation::class)->findBy([
        'passenger' => $user,
        'isValidatedByPassenger' => null,
        'aConfirmeParticipation' => true,
        'aParticipe' => true,
    ]);

    $forms = [];

    foreach ($reservations as $reservation) {
        $form = $this->createForm(ReservationValidationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation->setIsValidatedByPassenger(true);

            $passengerFeedback = $form->get('passengerFeedback')->getData();
            $passengerNote = $form->get('passengerNote')->getData();

            $covoiturage = $reservation->getCovoiturage();
            $driver = $covoiturage->getDriver();

            // Ajouter les crédits au chauffeur via MongoDB (en utilisant le CreditService)
            $amount = $covoiturage->getPrixPersonne();
            $creditService->addCredits($driver->getId(), $amount); // Ajouter des crédits

            $notificationService->notifyDriverOfValidation($driver, $reservation);

            // Enregistrement de l'avis
            if ($passengerNote || $passengerFeedback) {
                $avis = new Avis();
                $avis->setNote($passengerNote);
                $avis->setCommentaire($passengerFeedback);
                $avis->setStatut('en attente');
                $avis->setIsValidated(false);
                $avis->setReservation($reservation);
                $em->persist($avis);
            }

            // Marquer le trajet comme terminé
            $covoiturage->setIsCompleted(true);

            $em->flush();

            $this->addFlash('success', 'Trajet validé avec succès.');
            return $this->redirectToRoute('reservations_to_validate');
        }

        $forms[$reservation->getId()] = $form->createView();
    }

    return $this->render('utilisateur/reservations_to_validate.html.twig', [
        'participations' => $reservations,
        'participationForms' => $forms,
    ]);
}




#[Route('/reservation/{id}/signaler-probleme', name: 'reservation_signaler_probleme', methods: ['POST', 'GET'])]
public function signalerProbleme(
    int $id,
    Request $request,
    EntityManagerInterface $em,
    NotificationService $notificationService
): Response {
    $reservation = $em->getRepository(Reservation::class)->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('Réservation introuvable.');
    }

    $form = $this->createForm(ReservationValidationType::class, $reservation);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $reservation->setProblemeSignale(true);
        $detailsProbleme = $form->get('detailsProbleme')->getData();
        $reservation->setDetailsProbleme($detailsProbleme);

        $passengerFeedback = $form->get('passengerFeedback')->getData();
        $passengerNote = $form->get('passengerNote')->getData();

        if ($passengerNote || $passengerFeedback) {
            $avis = new Avis();
            $avis->setNote($passengerNote);
            $avis->setCommentaire($passengerFeedback);
            $avis->setStatut('en attente');
            $avis->setIsValidated(false);
            $avis->setReservation($reservation);
            $em->persist($avis);
        }

        $em->flush();

        $notificationService->notifyAdminOfIssue($reservation);

        $this->addFlash('success', 'Problème signalé avec succès.');

        return $this->redirectToRoute('reservations_to_validate');
    }

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
   
    $covoiturage = $em->getRepository(Covoiturage::class)->find($id);

    if (!$covoiturage) {
        throw $this->createNotFoundException('Covoiturage introuvable.');
    }

    
    foreach ($covoiturage->getReservations() as $reservation) {
        
        $reservation->setIsCompleted(true); 

        
        $notificationService->notifyPassengerToValidate($reservation);
    }

    
    $covoiturage->setIsCompleted(true);

    $em->flush();

    $this->addFlash('success', 'Trajet terminé, les passagers sont informés.');

    return $this->redirectToRoute('espace_utilisateur');
}

#[Route('/mes-avis', name: 'user_avis')]
public function mesAvis(AvisRepository $avisRepository): Response
{
    $utilisateur = $this->getUser();

    if (!$utilisateur instanceof Utilisateur) {
        return $this->redirectToRoute('app_login');
    }

    $avis = $avisRepository->findByUtilisateur($utilisateur);

    return $this->render('utilisateur/avis.html.twig', [
        'avisList' => $avis,
    ]);
}

   
}
