<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Form\AvisType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Reservation;
use App\Entity\Avis;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CovoiturageController extends AbstractController
{
    
    #[Route('/covoiturages', name: 'covoiturage_list')]
    public function listAllRides(CovoiturageRepository $covoiturageRepository): Response
    {
        $rides = $covoiturageRepository->findAll(); // ici on récupère tous les covoiturages
    
        return $this->render('covoiturage/list.html.twig', [
            'rides' => $rides,
        ]);
    }
    

    #[Route('/search', name: 'search_route', methods: ['GET', 'POST'])]
    public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): Response
    {
        // Création du formulaire avec les filtres avancés activés
        $form = $this->createForm(CovoiturageSearchType::class, null, ['showAdvancedFilters' => true]);
        $form->handleRequest($request);
    
        // Variables pour les résultats
        $rides = [];
        $searchPerformed = false;
        $noResults = false;
    
        // Si le formulaire est soumis avec des filtres
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
    
            // Traitement de la date de départ
            $dateDepartObj = $data['date_depart'] ?? null;
            if ($dateDepartObj && !$dateDepartObj instanceof \DateTimeInterface) {
                $dateDepartObj = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartObj);
            }
    
            if ($dateDepartObj) {
                $rides = $covoiturageRepository->findAvailableRides(
                    $data['lieu_depart'],
                    $data['lieu_arrivee'],
                    $dateDepartObj
                );
    
                // Sauvegarde des résultats dans la session
                $session->set('search_results', $rides);
                $session->set('search_criteria', [
                    'date_depart' => $dateDepartObj->format('Y-m-d'),
                    'lieu_depart' => $data['lieu_depart'],
                    'lieu_arrivee' => $data['lieu_arrivee'],
                    'is_eco' => $data['is_eco'],
                    'max_price' => $data['max_price'],
                    'max_duration' => $data['max_duration'],
                    'min_rating' => $data['min_rating'],
                ]);
    
                $searchPerformed = true;
                $noResults = empty($rides);
            }
        } else {
            // Si aucun formulaire n'est soumis, on réinitialise les critères
            if ($session->get('search_results') === null) {
                $rides = $covoiturageRepository->findAvailableRides('', '', new \DateTime('now')); // Recherche simple
                $session->set('search_results', $rides);
            } else {
                $rides = $session->get('search_results', []);
            }
        }
    
        // Si la requête est AJAX,on ne renvoie que la liste des trajets
        if ($request->isXmlHttpRequest()) {
            return $this->render('search/_rides_list.html.twig', [
                'rides' => $rides,
                'noResults' => $noResults,
            ]);
        }
    
        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides,
            'searchPerformed' => $searchPerformed,
            'noResults' => $noResults,
        ]);
    }
    


    #[Route('/covoiturage/{id}', name: 'covoiturage_details')]
    

public function details(int $id, CovoiturageRepository $covoiturageRepository, Request $request, EntityManagerInterface $em): Response
{
    // je récupère le covoiturage
    $ride = $covoiturageRepository->find($id);
    if (!$ride) {
        throw $this->createNotFoundException('Covoiturage non trouvé.');
    }

    // je vérifie si le conducteur existe
    $driver = $ride->getDriver();

    // je récupère les avis du conducteur
    $driverReviews = $driver ? $driver->getReviews() : [];

    // Si la collection est une instance de PersistentCollection, je l'initialise
    if ($driverReviews instanceof \Doctrine\ORM\PersistentCollection) {
        $driverReviews->initialize(); // Assuronsnvous ic que la collection est initialisée
    }

    // Je récupère les préférences du conducteur
    $driverPreferences = $driver ? $driver->getPreferences() : null;

    // Je crée un nouvel avis
    $review = new Avis();
    $form = $this->createForm(AvisType::class, $review);
    $form->handleRequest($request);

    // Si le formulaire est soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        // je lie l'avis à l'utilisateur actuel
        $review->setUser($this->getUser());
        $review->setStatut('actif'); 

        // j'ajoute l'avis au conducteur du covoiturage
        $ride->getDriver()->addReview($review);

        // on persist et flush
        $em->persist($review);
        $em->flush();

        // j'ajoute un message flash pour informer l'utilisateur que l'avis a été ajouté avec succès
        $this->addFlash('success', 'Votre avis a été ajouté avec succès.');

        // Je redirige vers la page de détails du covoiturage après l'ajout de l'avis
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    // Je passe les données à la vue
    return $this->render('covoiturage/details.html.twig', [
        'ride' => $ride,
        'driverReviews' => $driverReviews,
        'driverPreferences' => $driverPreferences,  
        'form' => $form->createView(),
    ]);
}

    
    
    
    #[Route('/reservation/{id}/cancel', name: 'cancel_reservation', methods: ['POST'])]
    public function cancelReservation(int $id, EntityManagerInterface $em): JsonResponse
    {
        $reservation = $em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            return new JsonResponse(['success' => false, 'message' => 'Réservation introuvable.'], 404);
        }

        $em->remove($reservation);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Réservation annulée avec succès.']);
    }
}
