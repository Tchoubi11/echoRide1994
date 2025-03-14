<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Reservation;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CovoiturageController extends AbstractController
{
    
    #[Route('/covoiturages', name: 'covoiturage_list')]
    public function listAllRides(CovoiturageRepository $covoiturageRepository): Response
    {
        $rides = $covoiturageRepository->findAll(); // Récupère tous les covoiturages
    
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
    
                // Sauvegarde les résultats dans la session
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
            // Si aucun formulaire n'est soumis, réinitialiser les critères
            if ($session->get('search_results') === null) {
                $rides = $covoiturageRepository->findAvailableRides('', '', new \DateTime('now')); // Recherche simple
                $session->set('search_results', $rides);
            } else {
                $rides = $session->get('search_results', []);
            }
        }
    
        // Si la requête est AJAX, ne renvoyer que la liste des trajets
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
    public function details(int $id, CovoiturageRepository $covoiturageRepository): Response
    {
        $ride = $covoiturageRepository->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        return $this->render('covoiturage/details.html.twig', [
            'ride' => $ride
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
