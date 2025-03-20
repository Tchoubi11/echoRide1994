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

        if ($form->isSubmitted() && $form->isValid()) {
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

            // Sauvegarde en base de données
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Connexion automatique après l'inscription
            return $this->userAuthenticator->authenticateUser(
                $user,
                $this->authenticator,
                $request
            );
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
