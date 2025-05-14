<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\StatistiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\UtilisateurInformationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
   #[Route('/admin/dashboard', name: 'admin_dashboard')]

    public function index(StatistiqueService $stats): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupère les statistiques
        $covoiturages = $stats->getCovoituragesParJour();
        $credits = $stats->getCreditsParJour();
        $totalCredits = $stats->getTotalCredits();

        // Fusion des dates
        $dates = array_unique(array_merge(array_keys($covoiturages), array_keys($credits)));
        sort($dates);

        // Initialisation des données complètes avec valeurs 0
        $covoituragesParJour = [];
        $creditsParJour = [];

        foreach ($dates as $date) {
            $covoituragesParJour[$date] = $covoiturages[$date] ?? 0;
            $creditsParJour[$date] = $credits[$date] ?? 0;
        }

        // Préparation des données au format Chart.js (pour le controller Stimulus)
        $covoiturageChart = [
            'labels' => array_keys($covoituragesParJour),
            'datasets' => [[
                'label' => 'Covoiturages par jour',
                'data' => array_values($covoituragesParJour),
                'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                'borderColor' => 'rgba(75, 192, 192, 1)',
                'borderWidth' => 1
            ]]
        ];

        $creditsChart = [
            'labels' => array_keys($creditsParJour),
            'datasets' => [[
                'label' => 'Crédits par jour',
                'data' => array_values($creditsParJour),
                'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1
            ]]
        ];

        return $this->render('admin/index.html.twig', [
            'totalCredits' => $totalCredits,
            'covoiturageChart' => ['data' => $covoiturageChart],
            'creditsChart' => ['data' => $creditsChart],
        ]);
    }

    #[Route('/admin/utilisateur/{id}/suspend', name: 'admin_suspend_user')]
    public function suspendUser(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('danger', 'Impossible de suspendre un administrateur.');
            return $this->redirectToRoute('admin_utilisateur_liste');
        }

        $user->setIsSuspended(true);
        $em->flush();
        $this->addFlash('warning', 'Utilisateur suspendu.');
        return $this->redirectToRoute('admin_utilisateur_liste');
    }

    #[Route('/admin/utilisateur/{id}/reactivate', name: 'admin_reactivate_user')]
    public function reactivateUser(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->setIsSuspended(false);
        $em->flush();
        $this->addFlash('success', 'Utilisateur réactivé.');
        return $this->redirectToRoute('admin_utilisateur_liste');
    }

    #[Route('/admin/utilisateurs', name: 'admin_utilisateur_liste')]
    public function listeUtilisateurs(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $utilisateurs = $em->getRepository(Utilisateur::class)->findAll();

        return $this->render('admin/utilisateur_liste.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/admin/employe/creer', name: 'admin_employe_creer')]
public function creerEmploye(
    Request $request,
    EntityManagerInterface $em, 
    UserPasswordHasherInterface $passwordHasher
): Response {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $employe = new Utilisateur();
    $form = $this->createForm(UtilisateurInformationType::class, $employe);

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        $hashedPassword = $passwordHasher->hashPassword(
            $employe,
            $form->get('password')->getData()
        );
        $employe->setPassword($hashedPassword);

        
        $employe->setRoles(['ROLE_EMPLOYE']);
        $employe->setTypeUtilisateur('employe');

        $em->persist($employe);
        $em->flush();

        $this->addFlash('success', 'Employé créé avec succès.');
        return $this->redirectToRoute('admin_utilisateur_liste');
    }

    return $this->render('admin/employe_create.html.twig', [
        'form' => $form->createView(),
    ]);
}
}
