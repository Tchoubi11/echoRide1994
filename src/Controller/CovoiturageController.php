<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Preference;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Form\AvisType;
use App\Form\CovoiturageSearchType;
use App\Form\CovoiturageType;
use App\Form\PreferenceType;
use App\Form\UtilisateurType;
use App\Repository\CovoiturageRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CreditService;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;






class CovoiturageController extends AbstractController
{
    #[Route('/covoiturages', name: 'covoiturage_list')]
public function listAllRides(CovoiturageRepository $covoiturageRepository): Response
{
    if (!$this->getUser()) {
        $this->addFlash('warning', 'Veuillez vous connecter pour accéder aux covoiturages.');
        return $this->redirectToRoute('app_login');
    }

    $rides = $covoiturageRepository->findAll(); 
    return $this->render('covoiturage/list.html.twig', [
        'rides' => $rides,
    ]);
}

   #[Route('/search', name: 'search_route', methods: ['GET', 'POST'])]
public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): Response
{
    // Création du formulaire de recherche
    $form = $this->createForm(CovoiturageSearchType::class, null, ['showAdvancedFilters' => true]);
    $form->handleRequest($request);

    // Initialisons les variables de filtrage et autres données
    $rides = [];
    $searchPerformed = false;
    $now = new \DateTime();
    $quickQuery = $request->query->get('query');

    // Vérification de la requête rapide (quick query)
    if ($quickQuery) {
        // Recherche par lieu de départ ou d'arrivée avec la requête rapide
        $rides = $covoiturageRepository->createQueryBuilder('c')
            ->where('LOWER(c.lieu_depart) LIKE :search')
            ->orWhere('LOWER(c.lieu_arrivee) LIKE :search')
            ->setParameter('search', '%' . strtolower($quickQuery) . '%')
            ->getQuery()
            ->getResult();

        $searchPerformed = true;

        // Si la requête est une requête Ajax, on retourne les résultats en JSON
        if ($request->isXmlHttpRequest()) {
            $data = [];
            foreach ($rides as $ride) {
                $data[] = [
                    'id' => $ride->getId(),
                    'driver' => $ride->getDriver()->getPseudo(),
                    'rating' => $ride->getDriver()->getRating(),
                    'nbPlace' => $ride->getNbPlace(),
                    'prixPersonne' => $ride->getPrixPersonne(),
                    'dateDepart' => $ride->getDateDepart()->format('d/m/Y H:i'),
                    'heureDepart' => $ride->getDateDepart()->format('H:i'),
                ];
            }

            return $this->json([
                'rides' => $data,
                'noResults' => count($data) === 0,
            ]);
        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides,
            'searchPerformed' => $searchPerformed,
            'query' => $quickQuery,
        ]);
    }

    // Recherche de covoiturages enregistrés si une recherche a été effectuée via le formulaire ou la session
    if ($request->isMethod('GET') && $session->has('search_criteria')) {
        $data = $session->get('search_criteria');
        $dateDepartObj = new \DateTime($data['date_depart']);

        $rides = $covoiturageRepository->findAvailableRides(
            $data['lieu_depart'],
            $data['lieu_arrivee'],
            $dateDepartObj
        );
        $searchPerformed = true;
    }

    // Traitement du formulaire de recherche (si soumis et valide)
    if ($form->isSubmitted() && $form->isValid()) {
        // Récupérons les données du formulaire
        $data = $form->getData();
        $dateDepartObj = $data['date_depart'] ?? $now;

        // Vérifions si la date de départ est valide, sinon définissons la à la date actuelle
        if (!$dateDepartObj instanceof \DateTimeInterface) {
            $dateDepartObj = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartObj);
        }

        // Recherche des covoiturages disponibles avec les filtres
        $rides = $covoiturageRepository->findAvailableRides(
            $data['lieu_depart'],
            $data['lieu_arrivee'],
            $dateDepartObj
        );

        // Sauvegardons les critères de recherche dans la session
        $session->set('search_criteria', [
            'lieu_depart' => $data['lieu_depart'],
            'lieu_arrivee' => $data['lieu_arrivee'],
            'date_depart' => $dateDepartObj->format('Y-m-d'),
        ]);

        $searchPerformed = true;

        
        if ($request->isXmlHttpRequest()) {
            $data = [];
            foreach ($rides as $ride) {
                $data[] = [
                    'id' => $ride->getId(),
                    'driver' => $ride->getDriver()->getPseudo(),
                    'rating' => $ride->getDriver()->getRating(),
                    'nbPlace' => $ride->getNbPlace(),
                    'prixPersonne' => $ride->getPrixPersonne(),
                    'dateDepart' => $ride->getDateDepart()->format('d/m/Y H:i'),
                    'heureDepart' => $ride->getDateDepart()->format('H:i'),
                ];
            }

            return $this->json([
                'rides' => $data,
                'noResults' => count($data) === 0,
            ]);
        }
    }

    // Rendu de la vue avec les résultats de la recherche
    return $this->render('search/index.html.twig', [
        'form' => $form->createView(),
        'rides' => $rides,
        'searchPerformed' => $searchPerformed,
    ]);
}



    #[Route('/covoiturage/{id}', name: 'covoiturage_details')]
public function details(
    int $id,
    CovoiturageRepository $covoiturageRepository,
    Request $request,
    EntityManagerInterface $em,
    CreditService $creditService,
    AuthorizationCheckerInterface $security
): Response {
    $ride = $covoiturageRepository->find($id);
    if (!$ride) {
        throw $this->createNotFoundException('Covoiturage non trouvé.');
    }

    $driver = $ride->getDriver();
    $driverCar = $driver ? $driver->getVoitures()->first() : null;

    $driverReviews = [];
    if ($driver) {
        foreach ($driver->getCovoiturages() as $covoiturage) {
            foreach ($covoiturage->getReservations() as $reservation) {
                if ($reservation->getAvis()) {
                    $driverReviews[] = $reservation->getAvis();
                }
            }
        }
    }

    $driverPreferences = $driver ? $driver->getPreference() : null;

    /** @var Utilisateur $user */
    $user = $this->getUser();
    $userPreferences = $user ? $user->getPreference() : null;

    if (!$userPreferences && $user) {
        $userPreferences = new Preference();
        $userPreferences->setUtilisateur($user);
    }

    // Détection du mode "édition"
    $editPrefs = $request->query->getBoolean('editPrefs');

    $preferenceForm = $this->createForm(PreferenceType::class, $userPreferences);
    $preferenceForm->handleRequest($request);

    if ($preferenceForm->isSubmitted() && $preferenceForm->isValid()) {
        $em->persist($userPreferences);
        $em->flush();
        $this->addFlash('success', 'Préférences enregistrées avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    // Gestion du formulaire d'avis
    $review = new Avis();
    $form = $this->createForm(AvisType::class, $review);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $reservation = $em->getRepository(Reservation::class)->findOneBy([
            'covoiturage' => $ride,
            'passenger' => $user
        ]);

        if (!$reservation) {
            $this->addFlash('danger', 'Vous devez avoir une réservation pour laisser un avis.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }

        $review->setReservation($reservation);
        $review->setStatut('actif');

        $em->persist($review);
        $em->flush();

        $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    // Récupération des crédits
    $credits = null;
    if ($user instanceof UserInterface) {
        $credits = $creditService->getUserCredits($user->getId());
    }

    $isOwner = $user && $userPreferences && $user->getId() === $userPreferences->getUtilisateur()->getId();

    return $this->render('covoiturage/details.html.twig', [
        'ride' => $ride,
        'driverReviews' => $driverReviews,
        'driverPreferences' => $driverPreferences,
        'driverCar' => $driverCar,
        'form' => $form->createView(),
        'rideId' => $id,
        'userPreferences' => $userPreferences,
        'preferenceForm' => $preferenceForm->createView(),
        'credits' => $credits,
        'isOwner' => $isOwner,
        'editPrefs' => $editPrefs,
    ]);
}

    #[Route('/profil/modifier', name: 'edit_user')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        /** @var Utilisateur $user */
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
    Request $request,
    CreditService $creditService
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
        $notifier->notifyPassengersOfCancellation($covoiturage);
        $covoiturage->setIsCancelled(true);
        $em->flush();
        $this->addFlash('success', 'Covoiturage annulé avec succès.');
    } else {
        $reservation = $em->getRepository(Reservation::class)->findOneBy([
            'covoiturage' => $covoiturage,
            'passenger' => $user
        ]);

        if (!$reservation) {
            throw $this->createAccessDeniedException('Vous n’avez pas de réservation sur ce covoiturage.');
        }

        $places = $reservation->getPlacesReservees();
        $creditService->addCredits($user->getId(), $places);
        $covoiturage->setNbPlace($covoiturage->getNbPlace() + $places);
        $em->remove($reservation);
        $em->flush();
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
    /** @var Utilisateur $user */
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    if ($user->getTypeUtilisateur() !== 'chauffeur') {
        return $this->redirectToRoute('profile');
    }

    $covoiturage = new Covoiturage();
    $covoiturage->setDriver($user);

    // Initialisation des préférences si null
    if ($covoiturage->getPreference() === null) {
        $preference = new Preference();
        $preference->setFumeur(false);
        $preference->setAnimaux(false);
        $preference->setAutres([]);
        $covoiturage->setPreference($preference);
    }

    $form = $this->createForm(CovoiturageType::class, $covoiturage, ['user' => $user]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if (!$covoiturage->getDateArrivee() && $covoiturage->getDateDepart()) {
            $dateDepart = $covoiturage->getDateDepart();

            if ($dateDepart instanceof \DateTimeInterface) {
                $dateArrivee = \DateTime::createFromFormat('Y-m-d H:i:s', $dateDepart->format('Y-m-d H:i:s'));
                if ($dateArrivee !== false) {
                    $dateArrivee->modify('+2 hours');
                    $covoiturage->setDateArrivee($dateArrivee);
                }
            }
        }

        $em->persist($covoiturage);
        $em->flush();

        $this->addFlash('success', 'Votre covoiturage a été créé avec succès !');
        return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
    }

    return $this->render('covoiturage/create.html.twig', [
        'formCovoiturage' => $form->createView(),
    ]);
}


    #[Route('/covoiturage/{id}/demarrer', name: 'demarrer_covoiturage')]
    public function demarrer(int $id, EntityManagerInterface $em): Response
    {
        $ride = $em->getRepository(Covoiturage::class)->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        $ride->setIsStarted(true);
        $ride->setStartAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Covoiturage démarré avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    #[Route('/covoiturage/{id}/arriver', name: 'arriver_covoiturage')]
    public function arriver(int $id, EntityManagerInterface $em): Response
    {
        $ride = $em->getRepository(Covoiturage::class)->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        if (!$ride->isStarted()) {
            $this->addFlash('warning', 'Vous ne pouvez terminer un trajet qui n’a pas commencé.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }

        $ride->setIsCompleted(true);
        $ride->setEndAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Trajet marqué comme terminé avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }
    

    #[Route('/covoiturage/{id}/valider-passagers', name: 'covoiturage_valider_passagers', methods: ['GET', 'POST'])]
public function validerPassagers(
    Covoiturage $covoiturage,
    Request $request,
    EntityManagerInterface $em
): Response {
    // Vérification que l'utilisateur est bien le conducteur
    if ($covoiturage->getDriver() !== $this->getUser()) {
        throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à valider ce covoiturage.");
    }

    // Validation des passagers si la méthode est POST
    if ($request->isMethod('POST')) {
        // Vérification de l'ID de covoiturage dans la requête pour éviter la soumission multiple
        $rideId = $request->request->get('covoiturage_id');
        if ($rideId != $covoiturage->getId()) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        // On boucle sur toutes les réservations pour mettre à jour la présence des passagers
        foreach ($covoiturage->getReservations() as $reservation) {
            $isPresent = $request->request->get('passager_'.$reservation->getId()) === 'on';
            $reservation->setAParticipe($isPresent);

            // Vérification et validation de l'avis pour chaque réservation
            if ($isPresent && $reservation->getAvis()) {
                $avis = $reservation->getAvis();
                $avis->setIsValidated(true); 
                $em->persist($avis); 
            }
        }

        // Persister les modifications
        $em->flush(); 

        // Ajouter un message flash pour indiquer que la validation des passagers a réussi
        $this->addFlash('success', 'Présence des passagers mise à jour avec succès.');

        // Rediriger vers la même page pour éviter la soumission multiple
        return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
    }

    // Rendu du formulaire pour valider les passagers
    return $this->render('covoiturage/valider_passagers.html.twig', [
        'covoiturage' => $covoiturage,
    ]);
}


#[Route('/covoiturage/{id}/modifier-preferences', name: 'edit_driver_preferences')]
public function editDriverPreferences(
    int $id,
    Request $request,
    EntityManagerInterface $em
): Response {
    $ride = $em->getRepository(Covoiturage::class)->find($id);
    if (!$ride) {
        throw $this->createNotFoundException('Covoiturage introuvable.');
    }

    $user = $this->getUser();

    // Vérifie que l'utilisateur est le conducteur
    if ($ride->getDriver() !== $user) {
        $this->addFlash('danger', 'Vous n\'êtes pas autorisé à modifier ces préférences.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    $driver = $ride->getDriver();
    $preferences = $driver->getPreference();

    if (!$preferences) {
        $preferences = new Preference();
        $preferences->setUtilisateur($driver);
    }

    $form = $this->createForm(PreferenceType::class, $preferences);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($preferences);
        $em->flush();

        $this->addFlash('success', 'Préférences mises à jour avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    return $this->render('preference/edit.html.twig', [
        'form' => $form->createView(),
        'ride' => $ride,
    ]);
}


}
