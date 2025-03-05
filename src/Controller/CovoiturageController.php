<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;



class CovoiturageController extends AbstractController
{
    #[Route('/search', name: 'search_route')]
    public function search(Request $request, CovoiturageRepository $covoiturageRepository): Response
    {
       
        $form = $this->createForm(CovoiturageSearchType::class);
        $form->handleRequest($request);

        $rides = [];
        $nextRide = null;

        
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $departure = $data['departure'];
            $destination = $data['destination'];
            $date = $data['date'];

            
            $rides = $covoiturageRepository->findAvailableRides($departure, $destination, $date);

            
            if (empty($rides)) {
                $nextRide = $covoiturageRepository->findNextAvailableRide($departure, $destination, $date);
            }
        }

      
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'rides' => $rides,
                'nextRide' => $nextRide ? $nextRide : null
            ]);
        }

        
        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides,
            'nextRide' => $nextRide,
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'reservation_cancel', methods: ['POST'])]
    public function cancelReservationAjax(int $id, CovoiturageRepository $covoiturageRepository, EntityManagerInterface $em): JsonResponse
    {
        $reservation = $covoiturageRepository->findReservationById($id);
        $user = $this->getUser();
    
        if (!$reservation || $reservation->getPassenger() !== $user) {
            return $this->json(['success' => false, 'message' => 'Réservation non trouvée ou accès refusé.'], Response::HTTP_FORBIDDEN);
        }
    
        $ride = $reservation->getCovoiturage();
        $ride->removePassenger($user);
        $ride->setNbPlace($ride->getNbPlace() + 1);
    
        $em->remove($reservation);
        $em->flush();
    
        return new JsonResponse(['success' => true, 'message' => 'Réservation annulée.']);

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


}

