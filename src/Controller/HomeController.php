<?php

// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CovoiturageRepository;
use App\Form\CovoiturageSearchType;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, CovoiturageRepository $covoiturageRepository): Response
    {
        // Créer et traiter le formulaire en désactivant les filtres avancés
        $form = $this->createForm(CovoiturageSearchType::class, null, [
            'showAdvancedFilters' => false
        ]);
        $form->handleRequest($request);

        $covoiturages = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $departure = $data['lieu_depart'];
            $destination = $data['lieu_arrivee'];
            $date = $data['date_depart'];

            // Récupérer les covoiturages selon les critères
            $covoiturages = $covoiturageRepository->findAvailableRides($departure, $destination, $date);
        }

        return $this->render('home/index.html.twig', [
            'form' => $form->createView(),
            'covoiturages' => $covoiturages,
        ]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return $this->render('security/login.html.twig');
    }

    #[Route('/contact', name: 'contact')]
    public function contact(): Response
    {
        return $this->render('contact/index.html.twig');
    }
}
