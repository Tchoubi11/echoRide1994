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
    
    #[Route('/covoiturage', name: 'covoiturage_home')]
    public function covoiturageIndex(Request $request, CovoiturageRepository $covoiturageRepository): Response
    {
        $form = $this->createForm(CovoiturageSearchType::class);
        $form->handleRequest($request);
    
        $rides = []; 
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $rides = $covoiturageRepository->findAvailableRides(
                $data['departure'],
                $data['destination'],
                $data['date']
            );
        }
    
        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides, 
        ]);
    }

    #[Route('/search', name: 'search_route', methods: ['GET', 'POST'])]
    public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): Response
    {
        $form = $this->createForm(CovoiturageSearchType::class);
        $form->handleRequest($request);

        $rides = [];
        $searchPerformed = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

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

                $session->set('search_criteria', [
                    'date_depart' => $dateDepartObj->format('Y-m-d'),
                    'lieu_depart' => $data['lieu_depart'],
                    'lieu_arrivee' => $data['lieu_arrivee']
                ]);

                $searchPerformed = true;
            }
        } else {
            $searchCriteria = $session->get('search_criteria', []);
            if (!empty($searchCriteria) && isset($searchCriteria['date_depart'])) {
                try {
                    $dateDepartObj = new \DateTime($searchCriteria['date_depart']);
                    $rides = $covoiturageRepository->findAvailableRides(
                        $searchCriteria['lieu_depart'],
                        $searchCriteria['lieu_arrivee'],
                        $dateDepartObj
                    );
                    $searchPerformed = true;
                } catch (\Exception $e) {
                    
                }
            }
        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides,
            'searchPerformed' => $searchPerformed,
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
