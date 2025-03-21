<?php

namespace App\Controller;

use App\Entity\Utilisateur; 
use App\Form\RegistrationFormType;
use App\Security\AppAuthenticator; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private UserAuthenticatorInterface $userAuthenticator;
    private AppAuthenticator $authenticator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->userAuthenticator = $userAuthenticator;
        $this->authenticator = $authenticator;
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request): Response
{
    $user = new Utilisateur();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    // Vérifier d'abord si le formulaire a été soumis
    if ($form->isSubmitted()) {
        // Ensuite, vérifier si le formulaire est valide
        if ($form->isValid()) {
            // Vérification du mot de passe avant hachage
            $plainPassword = $form->get('password')->getData();
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est requis.');
                return $this->redirectToRoute('app_register');
            }

            // Hachage du mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            // Attribution de 20 crédits par défaut
            $user->setCredits(20);

            // Sauvegarde de l'utilisateur en base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Authentification automatique après l'inscription
            $this->userAuthenticator->authenticateUser(
                $user,
                $this->authenticator, // Utilisation du AppAuthenticator pour l'authentification
                $request
            );

            // Message de succès et redirection vers la page de connexion
            $this->addFlash('success', 'Votre compte a été créé avec succès. Bienvenue !');
            return $this->redirectToRoute('app_login');
        } else {
            // Si le formulaire n'est pas valide, afficher un message d'erreur
            $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire.');
        }
    }

    // Retour du formulaire d'inscription si non soumis ou invalide
    return $this->render('registration/register.html.twig', [
        'registrationForm' => $form->createView(),
    ]);
}

}
