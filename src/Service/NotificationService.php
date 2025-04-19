<?php 

namespace App\Service;

use App\Entity\Covoiturage;
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
    
        // ❌ Plus de flush ici
        // On retourne les passagers pour les mails
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
                // Log optionnel
            }
        }
    }
    
    

    public function notifyPassengerOfCancellation(Utilisateur $passenger, Covoiturage $covoiturage): void
    {
        // ✅ Flush AVANT envoi pour s'assurer que les modifs sont bien persistées
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
            // Optionnel : log erreur
        }
    }
}
