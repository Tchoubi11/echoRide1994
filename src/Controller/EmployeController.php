<?php 

namespace App\Controller;

use App\Entity\Avis;
use App\Repository\AvisRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/espace-employe')]
final class EmployeController extends AbstractController
{
    
    #[Route('/', name: 'app_employe')]
    public function index(): Response
    {
        return $this->render('employe/index.html.twig');
    }

    // Liste des avis à modérer
    #[Route('/avis', name: 'moderation_avis_liste')]
    public function avisNonValides(AvisRepository $avisRepository): Response
    {
        $avisNonValidés = $avisRepository->findBy(['isValidated' => false]);

        return $this->render('employe/avis.html.twig', [
            'avisList' => $avisNonValidés,
        ]);
    }

    // Valider un avis
    #[Route('/avis/valider/{id}', name: 'moderation_avis_valider')]
    public function validerAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            throw $this->createNotFoundException();
        }

        $avis->setIsValidated(true);
        $em->flush();

        $this->addFlash('success', 'Avis validé.');
        return $this->redirectToRoute('moderation_avis_liste');
    }

    // Refuser un avis
    #[Route('/avis/refuser/{id}', name: 'moderation_avis_refuser')]
    public function refuserAvis(int $id, EntityManagerInterface $em): Response
    {
        $avis = $em->getRepository(Avis::class)->find($id);
        if (!$avis) {
            throw $this->createNotFoundException();
        }

        $em->remove($avis);
        $em->flush();

        $this->addFlash('danger', 'Avis refusé et supprimé.');
        return $this->redirectToRoute('moderation_avis_liste');
    }

    // Liste des trajets signalés comme problématiques
    #[Route('/problemes', name: 'employe_problemes_liste')]
    public function problemes(ReservationRepository $repo): Response
    {
        $reservationsAvecProblemes = $repo->findBy(['problemeSignale' => true]);

        return $this->render('employe/problemes.html.twig', [
            'reservations' => $reservationsAvecProblemes
        ]);
    }
}
