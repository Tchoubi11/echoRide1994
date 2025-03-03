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
        // Create and handle the form
        $form = $this->createForm(CovoiturageSearchType::class);
        $form->handleRequest($request);

        $rides = [];
        $nextRide = null;

        // If the form is submitted and valid
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $departure = $data['departure'];
            $destination = $data['destination'];
            $date = $data['date'];

            // Search for available rides
            $rides = $covoiturageRepository->findAvailableRides($departure, $destination, $date);

            // If no rides are found, suggest the next available ride
            if (empty($rides)) {
                $nextRide = $covoiturageRepository->findNextAvailableRide($departure, $destination, $date);
            }
        }

        // Handle the AJAX request and return JSON response
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'rides' => $rides,
                'nextRide' => $nextRide ? $nextRide : null
            ]);
        }

        // Otherwise, return the rendered template with the form and results
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

}

