<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;


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

        
        return $this->render('covoiturage/search.html.twig', [
            'form' => $form->createView(),
            'rides' => $rides,
            'nextRide' => $nextRide,
        ]);
    }

    #[Route('/covoiturage/{id}/cancel', name: 'covoiturage_cancel')]
public function cancelReservation(int $id, CovoiturageRepository $covoiturageRepository, EntityManagerInterface $em): Response
{
    $ride = $covoiturageRepository->find($id);
    $user = $this->getUser();

    if (!$ride || !$user) {
        throw $this->createNotFoundException('Erreur de réservation.');
    }

    if (!$ride->getPassengers()->contains($user)) {
        $this->addFlash('danger', 'Vous n’êtes pas inscrit à ce covoiturage.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    $ride->removePassenger($user);
    $ride->setNbPlace($ride->getNbPlace() + 1);

    $em->persist($ride);
    $em->flush();

    $this->addFlash('success', 'Votre réservation a été annulée.');

    return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
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

