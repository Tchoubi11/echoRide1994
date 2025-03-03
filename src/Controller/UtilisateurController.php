<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;
use App\Entity\Image;

class UtilisateurController extends AbstractController
{
    private $entityManager;

    // Injection de l'EntityManagerInterface via le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/profile', name: 'user_profile')]
    public function profile(): Response
    {
        $utilisateur = $this->getUser();

        // Vérifie que l'utilisateur est connecté et est bien une instance de Utilisateur
        if (!$utilisateur instanceof Utilisateur) {
            return $this->redirectToRoute('app_login'); 
        }

        // Vérifie si l'utilisateur a une photo
        if (!$utilisateur->getPhoto()) {
            // Assigne une photo par défaut si nécessaire
            $defaultPhoto = new Image();
            $defaultPhoto->setImagePath('default-avatar.png');
            $utilisateur->setPhoto($defaultPhoto);

            // Sauvegarde de l'utilisateur après mise à jour de la photo
            $this->entityManager->persist($utilisateur);
            $this->entityManager->flush();
        }

        return $this->render('utilisateur/profile.html.twig', [
            'utilisateur' => $utilisateur, 
        ]);
    }
}
