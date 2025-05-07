<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Preference;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use App\Form\AvisType;
use App\Form\CovoiturageSearchType;
use App\Form\CovoiturageType;
use App\Form\PreferenceType;
use App\Form\UtilisateurType;
use App\Repository\CovoiturageRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReservationRepository;

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
        $now = new \DateTime(); 

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

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $dateDepartObj = $data['date_depart'] ?? $now;

            if (!$dateDepartObj instanceof \DateTimeInterface) {
                $dateDepartObj = \DateTime::createFromFormat('Y-m-d', (string) $dateDepartObj);
            }

            if ($dateDepartObj) {
                $rides = $covoiturageRepository->findAvailableRides(
                    $data['lieu_depart'],
                    $data['lieu_arrivee'],
                    $dateDepartObj
                );

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
        $ride = $covoiturageRepository->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        $driver = $ride->getDriver();
        $driverCar = $driver ? $driver->getVoitures()->first() : null;

        // 🔄 Récupération des avis via les réservations du conducteur
        $driverReviews = [];
        if ($driver) {
            foreach ($driver->getCovoiturages() as $covoiturage) {
                foreach ($covoiturage->getReservations() as $reservation) {
                    if ($reservation->getAvis()) {
                        $driverReviews[] = $reservation->getAvis();
                    }
                }
            }
        }

        $driverPreferences = $driver ? $driver->getPreference() : null;

        /** @var Utilisateur $user */
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

        // 🆕 Création de l'avis
        $review = new Avis();
        $form = $this->createForm(AvisType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reservation = $em->getRepository(Reservation::class)->findOneBy([
                'covoiturage' => $ride,
                'passenger' => $this->getUser()
            ]);

            if (!$reservation) {
                $this->addFlash('danger', 'Vous devez avoir une réservation pour laisser un avis.');
                return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
            }

            $review->setReservation($reservation);
            $review->setStatut('actif');

            $em->persist($review);
            $em->flush();

            $this->addFlash('success', 'Votre avis a été ajouté avec succès.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }

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
        /** @var Utilisateur $user */
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

    #[Route('/covoiturage/{id}/annuler', name: 'annuler_covoiturage', methods: ['POST'])]
    public function annuler(int $id, EntityManagerInterface $em, NotificationService $notifier, Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Utilisateur non connecté.');
        }

        $covoiturage = $em->getRepository(Covoiturage::class)->find($id);
        if (!$covoiturage) {
            throw $this->createNotFoundException('Covoiturage introuvable.');
        }

        $isDriver = $covoiturage->getDriver()->getId() === $user->getId();

        if ($isDriver) {
            $notifier->notifyPassengersOfCancellation($covoiturage);
            $covoiturage->setIsCancelled(true);
            $em->flush();
            $this->addFlash('success', 'Covoiturage annulé avec succès.');
        } else {
            $reservation = $em->getRepository(Reservation::class)->findOneBy([
                'covoiturage' => $covoiturage,
                'passenger' => $user
            ]);

            if (!$reservation) {
                throw $this->createAccessDeniedException('Vous n’avez pas de réservation sur ce covoiturage.');
            }

            $places = $reservation->getPlacesReservees();
            $user->setCredits($user->getCredits() + $places);
            $covoiturage->setNbPlace($covoiturage->getNbPlace() + $places);
            $em->remove($reservation);
            $em->flush();
            $notifier->notifyPassengerOfCancellation($user, $covoiturage);
            $this->addFlash('success', 'Réservation annulée avec succès.');
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        return $this->redirectToRoute('historique_covoiturages');
    }

    #[Route('/covoiturage/creer', name: 'covoiturage_create')]
public function create(Request $request, EntityManagerInterface $em): Response
{
    $user = $this->getUser();
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $covoiturage = new Covoiturage();
    $covoiturage->setDriver($user);
    $form = $this->createForm(CovoiturageType::class, $covoiturage, ['user' => $user]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        if (!$covoiturage->getDateArrivee() && $covoiturage->getDateDepart()) {
            $dateDepart = $covoiturage->getDateDepart();

            if ($dateDepart instanceof \DateTimeInterface) {
                $dateArrivee = \DateTime::createFromFormat('Y-m-d H:i:s', $dateDepart->format('Y-m-d H:i:s'));
                if ($dateArrivee !== false) {
                    $dateArrivee->modify('+2 hours');
                    $covoiturage->setDateArrivee($dateArrivee);
                }
            }
        }

        $em->persist($covoiturage);
        $em->flush();
        $this->addFlash('success', 'Votre covoiturage a été créé avec succès !');
        return $this->redirectToRoute('covoiturage_details', ['id' => $covoiturage->getId()]);
    }

    return $this->render('covoiturage/create.html.twig', [
        'formCovoiturage' => $form->createView(),
    ]);
}

    #[Route('/covoiturage/{id}/demarrer', name: 'demarrer_covoiturage')]
    public function demarrer(int $id, EntityManagerInterface $em): Response
    {
        $ride = $em->getRepository(Covoiturage::class)->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        $ride->setIsStarted(true);
        $ride->setStartAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Covoiturage démarré avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }

    #[Route('/covoiturage/{id}/arriver', name: 'arriver_covoiturage')]
    public function arriver(int $id, EntityManagerInterface $em): Response
    {
        $ride = $em->getRepository(Covoiturage::class)->find($id);
        if (!$ride) {
            throw $this->createNotFoundException('Covoiturage non trouvé.');
        }

        if (!$ride->isStarted()) {
            $this->addFlash('warning', 'Vous ne pouvez terminer un trajet qui n’a pas commencé.');
            return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
        }

        $ride->setIsCompleted(true);
        $ride->setEndAt(new \DateTimeImmutable());
        $em->flush();
        $this->addFlash('success', 'Trajet marqué comme terminé avec succès.');
        return $this->redirectToRoute('covoiturage_details', ['id' => $id]);
    }
    

    #[Route('/covoiturage/{id}/valider-passagers', name: 'covoiturage_valider_passagers')]
public function validerPassagers(
    Covoiturage $covoiturage,
    Request $request,
    EntityManagerInterface $em,
    ReservationRepository $reservationRepo
): Response {
    if ($covoiturage->getDriver() !== $this->getUser()) {
        throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à valider ce covoiturage.");
    }

    if ($request->isMethod('POST')) {
        foreach ($covoiturage->getReservations() as $reservation) {
            $isPresent = $request->request->get('passager_'.$reservation->getId()) === 'on';
            $reservation->setAParticipe($isPresent);
        }
        $em->flush();
        $this->addFlash('success', 'Présence des passagers mise à jour avec succès.');
        return $this->redirectToRoute('covoiturage_valider_passagers', ['id' => $covoiturage->getId()]);
    }

    return $this->render('covoiturage/valider_passagers.html.twig', [
        'covoiturage' => $covoiturage,
    ]);
}
}
