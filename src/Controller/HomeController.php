<?php

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
    // Créer et traiter le formulaire
    $form = $this->createForm(CovoiturageSearchType::class);
    $form->handleRequest($request);

    $covoiturages = [];
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $departure = $data['departure'];
        $destination = $data['destination'];
        $date = $data['date'];

        // Récupérer les covoiturages selon les critères (à ajuster selon la logique de votre application)
        $covoiturages = $covoiturageRepository->findByCriteria($departure, $destination, $date);
    }

    return $this->render('home/index.html.twig', [
        'form' => $form->createView(),
        'covoiturages' => $covoiturages,
    ]);
}

}
