<?php

// src/Controller/HomeController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\CovoiturageRepository;
use App\Form\CovoiturageSearchType;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;


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
    public function login(Request $request, AuthenticationUtils $authenticationUtils, LoggerInterface $securityLogger): Response
    {
        $session = $request->getSession();
    
        // Vérifie si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            $redirectUrl = $session->get('redirect_after_login', null);
            $session->remove('redirect_after_login'); // Supprime après récupération
            return $redirectUrl ? $this->redirect($redirectUrl) : $this->redirectToRoute('covoiturage_list');
        }
    
        // Récupère les erreurs de connexion
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
    
        // Log de sécurité : en cas d'échec de connexion
        if ($error) {
            $securityLogger->error('Échec de la tentative de connexion', [
                'username' => $lastUsername, 
                'error' => $error->getMessageKey()
            ]);
        }
    
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        
    }

}
