<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;   
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search_route')]

    public function search(Request $request, CovoiturageRepository $covoiturageRepository): Response
{
    //On crée et traite le formulaire
    $form = $this->createForm(CovoiturageSearchType::class);
    $form->handleRequest($request);

    // On initialise les variables
    $rides = [];
    $nextRide = null;

    // Si le formulaire est soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $departure = $data['departure'];
        $destination = $data['destination'];
        $date = $data['date'];

        // On recherche des covoiturages disponibles
        $rides = $covoiturageRepository->findAvailableRides($departure, $destination, $date);

        // Si aucun covoiturage trouvé, on propose le prochain covoiturage disponible
        if (empty($rides)) {
            $nextRide = $covoiturageRepository->findNextAvailableRide($departure, $destination, $date);
        }
    }

    // Affichage du formulaire et des résultats
    return $this->render('search/index.html.twig', [
        'form' => $form->createView(),
        'rides' => $rides,
        'nextRide' => $nextRide,  
    ]);
}

}
