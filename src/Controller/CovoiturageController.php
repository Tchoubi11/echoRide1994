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
use App\Entity\Covoiturage;
use App\Entity\Reservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Utilisateur;
use App\Form\CovoiturageType;



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

//Annulation d’un covoiturage entier par le conducteur

#[Route('/covoiturage/{id}/annuler', name: 'annuler_covoiturage')]
    public function annuler(int $id, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();  // Cette ligne utilise la classe Utilisateur
        $covoiturage = $em->getRepository(Covoiturage::class)->find($id);

        if (!$covoiturage) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        if ($covoiturage->getDriver() === $user) {
            // Annulation par chauffeur
            $reservations = $covoiturage->getReservations();

            foreach ($reservations as $reservation) {
                $passenger = $reservation->getPassenger();
                
                // Ajouter les crédits
                $passenger->setCredits($passenger->getCredits() + $reservation->getPlacesReservees());

                // Supprimer la réservation
                $em->remove($reservation);

                // Réajuster les places disponibles dans le covoiturage
                $covoiturage->setNbPlace($covoiturage->getNbPlace() + $reservation->getPlacesReservees());

                // Envoyer un email avec Symfony Mailer en utilisant Twig
                $email = (new Email())
                    ->from('noreply@tonsite.com')
                    ->to($passenger->getEmail())
                    ->subject('Annulation de covoiturage')
                    ->html($this->renderView(
                        'emails/annulation_covoiturage.html.twig', // Le template Twig
                        [
                            'prenom' => $passenger->getPrenom(),
                            'depart' => $covoiturage->getLieuDepart(),
                            'arrivee' => $covoiturage->getLieuArrivee(),
                            'date' => $covoiturage->getDateDepart()->format('d/m/Y H:i'),
                        ]
                    ));

                $mailer->send($email);
            }

            $em->remove($covoiturage);
            $this->addFlash('success', 'Covoiturage annulé, les passagers ont été notifiés.');

        } elseif ($reservation = $em->getRepository(Reservation::class)->findOneBy([
            'covoiturage' => $covoiturage,
            'passenger' => $user
        ])) {
            // Annulation par passager
            $user->setCredits($user->getCredits() + $reservation->getPlacesReservees());
            
            // Réajuster les places disponibles dans le covoiturage
            $covoiturage->setNbPlace($covoiturage->getNbPlace() + $reservation->getPlacesReservees());

            // Supprimer la réservation
            $em->remove($reservation);
            $this->addFlash('success', 'Votre réservation a été annulée.');

        } else {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler ce covoiturage.');
        }

        $em->flush();

        return $this->redirectToRoute('historique_covoiturages');
    }


    #[Route('/covoiturage/creer', name: 'covoiturage_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
    
        // Si l'utilisateur n'est pas connecté, on le redirige vers la page de connexion
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
    
        // Créer un nouvel objet Covoiturage
        $covoiturage = new Covoiturage();
        
        // Définir le conducteur (l'utilisateur connecté)
        $covoiturage->setDriver($user);
    
        // Créer le formulaire de création de covoiturage
        $form = $this->createForm(CovoiturageType::class, $covoiturage, [
            'user' => $user, // Passer l'utilisateur comme option
        ]);
    
        // Traiter la requête du formulaire
        $form->handleRequest($request);
    
        // Vérifier si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Si la dateArrivee n'est pas définie, la calculer à partir de dateDepart
            if (!$covoiturage->getDateArrivee()) {
                $dateDepart = $covoiturage->getDateDepart();
                if ($dateDepart instanceof \DateTime) {
                    // Ajouter 2 heures à la date de départ pour définir la date d'arrivée
                    $dateArrivee = clone $dateDepart;  // Cloner pour ne pas modifier $dateDepart
                    $dateArrivee->modify('+2 hours');
                    $covoiturage->setDateArrivee($dateArrivee);
                }
            }
    
            // Sauvegarder le covoiturage dans la base de données
            $em->persist($covoiturage);
            $em->flush();
    
            // Ajouter un message flash pour informer l'utilisateur du succès
            $this->addFlash('success', 'Votre covoiturage a été créé avec succès !');
    
            // Rediriger vers la page de détails du covoiturage créé
            return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
        }
    
        // Afficher le formulaire de création de covoiturage
        return $this->render('covoiturage/create.html.twig', [
            'formCovoiturage' => $form->createView(),
        ]);
    }
    

}
