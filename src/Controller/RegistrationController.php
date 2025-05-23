<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Service\CreditService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    private CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $typeUtilisateur = strtolower($user->getTypeUtilisateur());
            $initialCredits = in_array($typeUtilisateur, ['passager', 'chauffeur']) ? 20 : 0;

            $this->creditService->getOrCreateCredit($user->getId(), $initialCredits);

            $this->addFlash('success', 'Votre compte a été créé avec succès !');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
