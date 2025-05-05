<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurInformationType;

use App\Service\StatistiqueService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(
        StatistiqueService $stats,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $covoiturages = $stats->getCovoituragesParJour();
        $credits = $stats->getCreditsParJour();
        $totalCredits = $stats->getTotalCredits();

        $covoiturageChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $covoiturageChart->setData([
            'labels' => array_keys($covoiturages),
            'datasets' => [[
                'label' => 'Covoiturages par jour',
                'data' => array_values($covoiturages),
                'borderColor' => 'rgb(75, 192, 192)',
                'fill' => false,
            ]],
        ]);

        $creditsChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $creditsChart->setData([
            'labels' => array_keys($credits),
            'datasets' => [[
                'label' => 'Crédits par jour',
                'data' => array_values($credits),
                'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                'borderColor' => 'rgba(255, 99, 132, 1)',
                'borderWidth' => 1,
            ]],
        ]);

        return $this->render('admin/index.html.twig', [
            'totalCredits' => $totalCredits,
            'covoiturageChart' => $covoiturageChart,
            'creditsChart' => $creditsChart,
        ]);
    }

    #[Route('/admin/employes/create', name: 'admin_employe_create')]
public function createEmploye(
    Request $request,
    EntityManagerInterface $em,
    UserPasswordHasherInterface $passwordHasher
): Response {
    $user = new Utilisateur();
    $form = $this->createForm(UtilisateurInformationType::class, $user); 

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $user->setRoles(['ROLE_EMPLOYE']);
        $user->setTypeUtilisateur('employe');

        
        $user->setCredits(null);

        $hashedPassword = $passwordHasher->hashPassword(
            $user,
            $user->getPassword()
        );
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Employé créé avec succès.');
        return $this->redirectToRoute('admin_dashboard');
    }

    return $this->render('admin/employe_create.html.twig', [
        'form' => $form->createView(),
    ]);
}

    
    
    #[Route('/admin/utilisateur/{id}/suspend', name: 'admin_suspend_user')]
    public function suspendUser(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $user->setIsSuspended(true);
        $em->flush();
        $this->addFlash('warning', 'Utilisateur suspendu.');
        return $this->redirectToRoute('admin_utilisateur_liste');
    }


    #[Route('/admin/utilisateur/{id}/reactivate', name: 'admin_reactivate_user')]
    public function reactivateUser(Utilisateur $user, EntityManagerInterface $em): Response
    {
        $user->setIsSuspended(false);
        $em->flush();
        $this->addFlash('success', 'Utilisateur réactivé.');
        return $this->redirectToRoute('admin_utilisateur_liste');
    }


    #[Route('/admin/utilisateurs', name: 'admin_utilisateur_liste')]
public function listeUtilisateurs(EntityManagerInterface $em): Response
{
    $utilisateurs = $em->getRepository(Utilisateur::class)->findAll();

    return $this->render('admin/utilisateur_liste.html.twig', [
        'utilisateurs' => $utilisateurs,
    ]);
}

}
