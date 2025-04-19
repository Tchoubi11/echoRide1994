<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Form\AvisType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Avis;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\PreferenceType;
use App\Entity\Preference;
use App\Form\UtilisateurType;
use App\Entity\Covoiturage;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Form\CovoiturageType;
use App\Service\NotificationService;
use Symfony\Component\HttpFoundation\JsonResponse;





class CovoiturageController extends AbstractController
{
    
    #[Route('/covoiturages', name: 'covoiturage_list')]
    public function listAllRides(CovoiturageRepository $covoiturageRepository): Response
    {
        $rides = $covoiturageRepository->findAll(); 
    
        return $this->render('covoiturage/list.html.twig', [
            'rides' => $rides,
        ]);
    }
    

    

    #[Route('/search', name: 'search_route', methods: ['GET', 'POST'])]
    public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): Response
    {
        $form = $this->createForm(CovoiturageSearchType::class, null, ['showAdvancedFilters' => true]);
        $form->handleRequest($request);
    
        $searchPerformed = false;
        $rides = [];
        $now = new \DateTime(); 
    
        // Je récupère les critères de recherche sauvegardés si la requête est GET 
        if ($request->isMethod('GET') && $session->has('search_criteria')) {
            $data = $session->get('search_criteria');
            $dateDepartObj = new \DateTime($data['date_depart']);
    
            // J'applique la recherche en fonction de la date fournie
            $rides = $covoiturageRepository->findAvailableRides(
                $data['lieu_depart'],
                $data['lieu_arrivee'],
                $dateDepartObj
            );
            $searchPerformed = true;
        }
    
        // Si le formulaire est soumis, je traite la recherche normalement
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $dateDepartObj = $data['date_depart'] ?? $now; // Si aucune date n'est spécifiée, je prends la date actuelle
    
            if (!$dateDepartObj instanceof \DateTimeInterface) {
                $dateDepartObj = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartObj);
            }
    
            if ($dateDepartObj) {
                // J'effectue la recherche en utilisant la date de départ fournie ou la date actuelle
                $rides = $covoiturageRepository->findAvailableRides(
                    $data['lieu_depart'],
                    $data['lieu_arrivee'],
                    $dateDepartObj
                );
    
                // Je sauvegarde les critères en session
                $session->set('search_criteria', [
                    'lieu_depart' => $data['lieu_depart'],
                    'lieu_arrivee' => $data['lieu_arrivee'],
                    'date_depart' => $dateDepartObj->format('Y-m-d')
                ]);
    
                $searchPerformed = true;
            }
        }
    
        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides,
            'searchPerformed' => $searchPerformed,
        ]);
    }
    


    #[Route('/covoiturage/{id}', name: 'covoiturage_details')]

    public function details(int $id, CovoiturageRepository $covoiturageRepository, Request $request, EntityManagerInterface $em): Response
    {
        // Je récupère le covoiturage
        $ride = $covoiturageRepository->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }
    
        // Je vérifie si le conducteur existe
        $driver = $ride->getDriver();
        $driverReviews = $driver ? $driver->getReviews() : [];
    
        // Je récupère la voiture du conducteur
        $driverCar = $driver ? $driver->getVoitures()->first() : null;
    
        // Si la collection est une instance de PersistentCollection, je l'initialise
        if ($driverReviews instanceof \Doctrine\ORM\PersistentCollection) {
            $driverReviews->initialize();
        }
    
        // Je récupère les préférences du conducteur
        $driverPreferences = $driver ? $driver->getPreference() : null;
    
        // Je récupère les préférences de l'utilisateur connecté
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $userPreferences = $user ? $user->getPreference() : null;
    
        
        if (!$userPreferences) {
            $userPreferences = new Preference();
            $userPreferences->setUtilisateur($user);
        }
    
        $preferenceForm = $this->createForm(PreferenceType::class, $userPreferences);
        $preferenceForm->handleRequest($request);
    
        if ($preferenceForm->isSubmitted() && $preferenceForm->isValid()) {
            $em->persist($userPreferences);
            $em->flush();
    
            $this->addFlash('success', 'Préférences enregistrées avec succès.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }
    
        // Je crée un nouvel avis
        $review = new Avis();
        $form = $this->createForm(AvisType::class, $review);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $review->setUser($this->getUser());
            $review->setStatut('actif');
            $ride->getDriver()->addReview($review);
    
            $em->persist($review);
            $em->flush();
    
            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }
    
        dump($user, $userPreferences);
    
        return $this->render('covoiturage/details.html.twig', [
            'ride' => $ride,
            'driverReviews' => $driverReviews,
            'driverPreferences' => $driverPreferences,
            'driverCar' => $driverCar,
            'form' => $form->createView(),
            'rideId' => $id,
            'userPreferences' => $userPreferences,
            'preferenceForm' => $preferenceForm->createView(), 
        ]);
    }
    


    #[Route('/profil/modifier', name: 'edit_user')]
public function edit(Request $request, EntityManagerInterface $em): Response
{
    /** @var \App\Entity\Utilisateur $user */
    $user = $this->getUser(); 

    $form = $this->createForm(UtilisateurType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if (in_array($user->getTypeUtilisateur(), ['chauffeur', 'les_deux']) && !$user->getVoitures()) {
            $this->addFlash('danger', 'Vous devez ajouter un véhicule pour être chauffeur.');
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('dashboard');
    }

    return $this->render('user/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/covoiturage/{id}/annuler', name: 'annuler_covoiturage', methods: ['POST'])]
public function annuler(
    int $id,
    EntityManagerInterface $em,
    NotificationService $notifier,
    Request $request
): Response {
    /** @var Utilisateur $user */
    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException('Utilisateur non connecté.');
    }

    $covoiturage = $em->getRepository(Covoiturage::class)->find($id);
    if (!$covoiturage) {
        throw $this->createNotFoundException('Covoiturage introuvable.');
    }

    $isDriver = $covoiturage->getDriver()->getId() === $user->getId();

    if ($isDriver) {
        // ✅ 1. Notifier et gérer les remboursements avant tout
        $notifier->notifyPassengersOfCancellation($covoiturage);

        // ✅ 2. Annuler le covoiturage une fois tout le reste est traité
        $covoiturage->setIsCancelled(true);
        $em->flush();

        $this->addFlash('success', 'Covoiturage annulé avec succès.');
    } else {
        // ✅ Annulation d’une réservation par un passager
        $reservation = $em->getRepository(Reservation::class)->findOneBy([
            'covoiturage' => $covoiturage,
            'passenger' => $user
        ]);
    
        if (!$reservation) {
            throw $this->createAccessDeniedException('Vous n’avez pas de réservation sur ce covoiturage.');
        }
    
        $places = $reservation->getPlacesReservees();
    
        // 💰 Remboursement et mise à jour des places
        $user->setCredits($user->getCredits() + $places);
        $covoiturage->setNbPlace($covoiturage->getNbPlace() + $places);
    
        // 🗑️ Suppression de la réservation
        $em->remove($reservation);
        $em->flush();
    
        // ✉️ Envoi du mail de confirmation d'annulation
        $notifier->notifyPassengerOfCancellation($user, $covoiturage);
    
        $this->addFlash('success', 'Réservation annulée avec succès.');
    }

    if ($request->isXmlHttpRequest()) {
        return new JsonResponse(['success' => true]);
    }

    return $this->redirectToRoute('historique_covoiturages');
}


    #[Route('/covoiturage/creer', name: 'covoiturage_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        // Si l'utilisateur n'est pas connecté, on le redirige vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // on crée un nouvel objet Covoiturage
        $covoiturage = new Covoiturage();
        
        // Définissons le conducteur (l'utilisateur connecté)
        $covoiturage->setDriver($user);
    
        // Création du formulaire de création de covoiturage
        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $user, 
        ]);
    
        // Traitons la requête du formulaire
        $form->handleRequest($request);
    
        // Vérifions si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Si la dateArrivee n'est pas définie,on la calcule à partir de dateDepart
            if (!$covoiturage->getDateArrivee()) {
                $dateDepart = $covoiturage->getDateDepart();
                if ($dateDepart instanceof \DateTime) {
                    // Ajoutons 2 heures à la date de départ pour définir la date d'arrivée
                    $dateArrivee = clone $dateDepart;  
                    $dateArrivee->modify('+2 hours');
                    $covoiturage->setDateArrivee($dateArrivee);
                }
            }
    
            // Sauvegardons le covoiturage dans la base de données
            $em->persist($covoiturage);
            $em->flush();
    
            // Ajoutons un message flash pour informer l'utilisateur du succès
            $this->addFlash('success', 'Votre covoiturage a été créé avec succès !');
    
            // Redirigeons vers la page de détails du covoiturage créé
            return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
        }
    
        // Affichons le formulaire de création de covoiturage
        return $this->render('covoiturage/create.html.twig', [
            'formCovoiturage' => $form->createView(),
        ]);
    }
    

}
