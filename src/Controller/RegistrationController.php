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

    // Debugging pour voir les données de la requête
    dump($request->request->all());

    // On vérifie si le formulaire est soumis
    dump($form->isSubmitted());

    if ($form->isSubmitted()) {
        dump($form->isValid());

        if ($form->isValid()) {
            // Vérification du mot de passe avec le bon champ
            $plainPassword = $form->get('plainPassword')->getData();
            if (!$plainPassword) {
                $this->addFlash('error', 'Le mot de passe est requis.');
                return $this->redirectToRoute('app_register');
            }

            // Hachage du mot de passe et ajout de crédits
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            $user->setCredits(20);

            // Sauvegarde de l'utilisateur
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Authentification automatique
            $this->userAuthenticator->authenticateUser($user, $this->authenticator, $request);

            // Message de succès et redirection
            $this->addFlash('success', 'Votre compte a été créé avec succès. Bienvenue !');
            return $this->redirectToRoute('app_home');
        } else {
            $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire.');
        }
    }

    return $this->render('registration/register.html.twig', [
        'registrationForm' => $form->createView(),
    ]);
}

}
