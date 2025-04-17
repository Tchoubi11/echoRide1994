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
    
        foreach ($reservations as $reservation) {
            $passenger = $reservation->getPassenger();
    
            // 🔒 Ignorer le conducteur s'il est aussi passager (réservation à son propre covoiturage)
            if ($passenger->getId() === $covoiturage->getDriver()->getId()) {
                continue;
            }
    
            // ✅ Remboursement des crédits
            $passenger->setCredits($passenger->getCredits() + $reservation->getPlacesReservees());
    
            // ✅ Suppression de la réservation
            $this->em->remove($reservation);
    
            // ✅ Envoi de l'email de notification
            $html = $this->twig->render('emails/annulation_covoiturage.html.twig', [
                'passenger' => $passenger,
                'driver' => $covoiturage->getDriver(),
                'covoiturage' => $covoiturage,
            ]);
    
            $email = (new Email())
                ->from('noreply@tonsite.com')
                ->to($passenger->getEmail() ?? 'dev@localhost')
                ->subject('🚗 Covoiturage annulé')
                ->html($html);
    
            $this->mailer->send($email);
        }
    
        // ✅ Sauvegarde des changements
        $this->em->flush();
    }
    

    // ✅ Quand un passager annule, il reçoit un email
    public function notifyPassengerOfCancellation(Utilisateur $passenger, Covoiturage $covoiturage): void
    {
        $html = $this->twig->render('emails/annulation_passager.html.twig', [
            'passenger' => $passenger,
            'covoiturage' => $covoiturage,
        ]);

        $email = (new Email())
            ->from('noreply@tonsite.com')
            ->to($passenger->getEmail())
            ->subject('🚗 Annulation de votre réservation')
            ->html($html);

        $this->mailer->send($email);
    }
}
