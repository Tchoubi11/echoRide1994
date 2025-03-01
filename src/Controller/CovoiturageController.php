<?php

namespace App\Controller;

use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CovoiturageController extends AbstractController
{
    #[Route('/covoiturage/{id}', name: 'covoiturage_details')]
    public function details(int $id, CovoiturageRepository $covoiturageRepository): Response
    {
        $ride = $covoiturageRepository->find($id);

        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        return $this->render('covoiturage/details.html.twig', [
            'ride' => $ride,
        ]);
    }
}
