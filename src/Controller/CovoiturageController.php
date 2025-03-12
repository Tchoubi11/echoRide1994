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
    

  // src/Controller/CovoiturageController.php

#[Route('/search', name: 'search_route', methods: ['GET', 'POST'])]
public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): Response
{
    // Création du formulaire avec les filtres avancés activés
    $form = $this->createForm(CovoiturageSearchType::class, null, ['showAdvancedFilters' => true]);
    $form->handleRequest($request);
    
    $rides = [];
    $searchPerformed = false;
    $noResults = false;

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        $dateDepartObj = $data['date_depart'] ?? null;
        if ($dateDepartObj && !$dateDepartObj instanceof \DateTimeInterface) {
            $dateDepartObj = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartObj);
        }

        if ($dateDepartObj) {
            // Rechercher les trajets selon les critères
            $rides = $covoiturageRepository->findAvailableRides(
                $data['lieu_depart'],
                $data['lieu_arrivee'],
                $dateDepartObj
            );

            // Mettre à jour la session avec les nouveaux critères de recherche
            $session->set('search_criteria', [
                'date_depart' => $dateDepartObj->format('Y-m-d'),
                'lieu_depart' => $data['lieu_depart'],
                'lieu_arrivee' => $data['lieu_arrivee']
            ]);

            $searchPerformed = true;
            $noResults = empty($rides);
        }
    } else {
        $session->remove('search_criteria');
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
