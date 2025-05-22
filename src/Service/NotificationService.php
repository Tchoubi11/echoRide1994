<?php 

namespace App\Service;

use App\Entity\Covoiturage;
use App\Entity\Reservation;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private Environment $twig
    ) {}

    public function notifyPassengersOfCancellation(Covoiturage $covoiturage): void
    {
        $reservations = $covoiturage->getReservations();
        $passengersToNotify = [];

        foreach ($reservations as $reservation) {
            $passenger = $reservation->getPassenger();

            if (!$passenger || $passenger->getId() === $covoiturage->getDriver()->getId()) {
                continue;
            }

            $passenger->setCredits($passenger->getCredits() + $reservation->getPlacesReservees());
            $this->em->remove($reservation);

            $passengersToNotify[] = $passenger;
        }

        $this->em->flush(); 

        foreach ($passengersToNotify as $passenger) {
            try {
                $html = $this->twig->render('emails/annulation_covoiturage.html.twig', [
                    'passenger' => $passenger,
                    'driver' => $covoiturage->getDriver(),
                    'covoiturage' => $covoiturage,
                ]);

                $email = (new Email())
                    ->from('noreply@tonsite.com')
                    ->to($passenger->getEmail() ?: 'dev@localhost')
                    ->subject('🚗 Covoiturage annulé')
                    ->html($html);

                $this->mailer->send($email);
            } catch (\Throwable $e) {
                error_log('[NotificationService] Erreur envoi mail annulation passager ID '.$passenger->getId().': ' . $e->getMessage());
            }
        }
    }

    public function notifyPassengerOfCancellation(Utilisateur $passenger, Covoiturage $covoiturage): void
    {
        $this->em->flush();

        try {
            $html = $this->twig->render('emails/annulation_passager.html.twig', [
                'passenger' => $passenger,
                'covoiturage' => $covoiturage,
            ]);

            $email = (new Email())
                ->from('noreply@tonsite.com')
                ->to($passenger->getEmail() ?: 'dev@localhost')
                ->subject('🚗 Annulation de votre réservation')
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log('[NotificationService] Erreur envoi mail annulation réservation passager ID '.$passenger->getId().': ' . $e->getMessage());
        }
    }

    public function notifyAdminOfIssue(Reservation $reservation): void
    {
        $adminEmail = 'admin@example.com'; 

        try {
            $html = $this->twig->render('emails/issue_reported.html.twig', [
                'reservation' => $reservation,
            ]);

            $email = (new Email())
                ->from('noreply@tonsite.com')
                ->to($adminEmail)
                ->subject('⚠️ Problème signalé dans une réservation')
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log('[NotificationService] Erreur envoi mail admin: ' . $e->getMessage());
        }
    }

    public function notifyPassengerToValidate(Reservation $reservation): void
    {
        try {
            $html = $this->twig->render('emails/validation_request.html.twig', [
                'reservation' => $reservation,
            ]);

            $email = (new Email())
                ->from('noreply@tonsite.com')
                ->to($reservation->getPassenger()->getEmail())
                ->subject('🚗 Merci de valider votre trajet')
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log('[NotificationService] Erreur envoi mail validation passager ID '.$reservation->getPassenger()->getId().': ' . $e->getMessage());
        }
    }

    public function notifyDriverOfValidation(Utilisateur $driver, Reservation $reservation): void
    {
        try {
            $html = $this->twig->render('emails/driver_validated.html.twig', [
                'reservation' => $reservation,
                'driver' => $driver,
            ]);

            $email = (new Email())
                ->from('noreply@tonsite.com')
                ->to($driver->getEmail())
                ->subject('🚗 Un passager a validé le trajet')
                ->html($html);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            error_log('[NotificationService] Erreur envoi mail chauffeur ID '.$driver->getId().': ' . $e->getMessage());
        }
    }
}
