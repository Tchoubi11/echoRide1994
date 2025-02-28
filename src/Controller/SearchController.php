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
        // ici on crée et traite le formulaire
        $form = $this->createForm(CovoiturageSearchType::class);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $departure = $data['departure'];
            $destination = $data['destination'];
            $date = $data['date'];

            // on recherche les covoiturages correspondant à la recherche
            $rides = $covoiturageRepository->findAvailableRides($departure, $destination, $date);

            // Si aucun trajet n'est trouvé,on propose une autre date (le trajet le plus proche)
            if (empty($rides)) {
                $nextRide = $covoiturageRepository->findNextAvailableRide($departure, $destination, $date);
                return $this->render('search/index.html.twig', [
                    'form' => $form->createView(),
                    'rides' => [],  // Aucun trajet trouvé
                    'nextRide' => $nextRide, // Propose le prochain trajet
                ]);
            }

            // Si des trajets sont trouvés
            return $this->render('search/index.html.twig', [
                'form' => $form->createView(),
                'rides' => $rides,
                'nextRide' => null, // Aucun trajet suivant nécessaire
            ]);
        }

        // Si le formulaire n'a pas encore été soumis
        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'rides' => [],  // Aucun trajet trouvé
            'nextRide' => null,  // Aucun trajet suivant nécessaire
        ]);
    }
}
