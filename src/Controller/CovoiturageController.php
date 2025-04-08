<?php

namespace App\Controller;

use App\Form\CovoiturageSearchType;
use App\Form\AvisType;
use App\Repository\CovoiturageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Avis;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Form\PreferenceType;
use App\Entity\Preference;
use App\Form\UtilisateurType;


class CovoiturageController extends AbstractController
{
    
    #[Route('/covoiturages', name: 'covoiturage_list')]
    public function listAllRides(CovoiturageRepository $covoiturageRepository): Response
    {
        $rides = $covoiturageRepository->findAll(); 
    
        return $this->render('covoiturage/list.html.twig', [
            'rides' => $rides,
        ]);
    }
    

    

    #[Route('/search', name: 'search_route', methods: ['GET', 'POST'])]
public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): Response
{
    $form = $this->createForm(CovoiturageSearchType::class, null, ['showAdvancedFilters' => true]);
    $form->handleRequest($request);

    $searchPerformed = false;
    $rides = [];

    // je récupère les critères de recherche sauvegardés si la requête est GET 
    if ($request->isMethod('GET') && $session->has('search_criteria')) {
        $data = $session->get('search_criteria');
        $dateDepartObj = new \DateTime($data['date_depart']);

        $rides = $covoiturageRepository->findAvailableRides(
            $data['lieu_depart'],
            $data['lieu_arrivee'],
            $dateDepartObj
        );
        $searchPerformed = true;
    }

    // Si le formulaire est soumis, j' effectue la recherche normalement
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();
        $dateDepartObj = $data['date_depart'] ?? null;

        if ($dateDepartObj && !$dateDepartObj instanceof \DateTimeInterface) {
            $dateDepartObj = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartObj);
        }

        if ($dateDepartObj) {
            // j'effectuer la recherche
            $rides = $covoiturageRepository->findAvailableRides(
                $data['lieu_depart'],
                $data['lieu_arrivee'],
                $dateDepartObj
            );

            // je Sauvegarde les critères en session
            $session->set('search_criteria', [
                'lieu_depart' => $data['lieu_depart'],
                'lieu_arrivee' => $data['lieu_arrivee'],
                'date_depart' => $dateDepartObj->format('Y-m-d')
            ]);

            $searchPerformed = true;
        }
    }

    return $this->render('search/index.html.twig', [
        'form' => $form->createView(),
        'rides' => $rides,
        'searchPerformed' => $searchPerformed,
    ]);
}


    #[Route('/covoiturage/{id}', name: 'covoiturage_details')]

    public function details(int $id, CovoiturageRepository $covoiturageRepository, Request $request, EntityManagerInterface $em): Response
    {
        // Je récupère le covoiturage
        $ride = $covoiturageRepository->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }
    
        // Je vérifie si le conducteur existe
        $driver = $ride->getDriver();
        $driverReviews = $driver ? $driver->getReviews() : [];
    
        // Je récupère la voiture du conducteur
        $driverCar = $driver ? $driver->getVoitures()->first() : null;
    
        // Si la collection est une instance de PersistentCollection, je l'initialise
        if ($driverReviews instanceof \Doctrine\ORM\PersistentCollection) {
            $driverReviews->initialize();
        }
    
        // Je récupère les préférences du conducteur
        $driverPreferences = $driver ? $driver->getPreference() : null;
    
        // Je récupère les préférences de l'utilisateur connecté
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        $userPreferences = $user ? $user->getPreference() : null;
    
        
        if (!$userPreferences) {
            $userPreferences = new Preference();
            $userPreferences->setUtilisateur($user);
        }
    
        $preferenceForm = $this->createForm(PreferenceType::class, $userPreferences);
        $preferenceForm->handleRequest($request);
    
        if ($preferenceForm->isSubmitted() && $preferenceForm->isValid()) {
            $em->persist($userPreferences);
            $em->flush();
    
            $this->addFlash('success', 'Préférences enregistrées avec succès.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }
    
        // Je crée un nouvel avis
        $review = new Avis();
        $form = $this->createForm(AvisType::class, $review);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $review->setUser($this->getUser());
            $review->setStatut('actif');
            $ride->getDriver()->addReview($review);
    
            $em->persist($review);
            $em->flush();
    
            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }
    
        dump($user, $userPreferences);
    
        return $this->render('covoiturage/details.html.twig', [
            'ride' => $ride,
            'driverReviews' => $driverReviews,
            'driverPreferences' => $driverPreferences,
            'driverCar' => $driverCar,
            'form' => $form->createView(),
            'rideId' => $id,
            'userPreferences' => $userPreferences,
            'preferenceForm' => $preferenceForm->createView(), 
        ]);
    }
    


    #[Route('/profil/modifier', name: 'edit_user')]
public function edit(Request $request, EntityManagerInterface $em): Response
{
    /** @var \App\Entity\Utilisateur $user */
    $user = $this->getUser(); 

    $form = $this->createForm(UtilisateurType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if (in_array($user->getTypeUtilisateur(), ['chauffeur', 'les_deux']) && !$user->getVoitures()) {
            $this->addFlash('danger', 'Vous devez ajouter un véhicule pour être chauffeur.');
        }

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Profil mis à jour avec succès !');
        return $this->redirectToRoute('dashboard');
    }

    return $this->render('user/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}

}
