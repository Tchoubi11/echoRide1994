<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface; 
use Symfony\Component\Mime\Email;             
use Twig\Environment;                         

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, MailerInterface $mailer, Environment $twig): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $subject = $request->request->get('subject');
            $message = $request->request->get('message');

            // Email à l’admin
            $htmlToAdmin = $twig->render('emails/contact_admin.html.twig', [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
            ]);

            $adminMessage = (new Email())
                ->from($email)
                ->to('admin@ecoride.com')
                ->subject("📩 Nouveau message de contact : $subject")
                ->html($htmlToAdmin);

            $mailer->send($adminMessage);

            // ✅ Email d’accusé de réception à l’utilisateur
            $htmlToUser = $twig->render('emails/accuse_reception.html.twig', [
                'name' => $name,
            ]);

            $confirmationMessage = (new Email())
                ->from('noreply@ecoride.com')
                ->to($email)
                ->subject('📬 Votre message a bien été reçu')
                ->html($htmlToUser);

            $mailer->send($confirmationMessage);

            $this->addFlash('success', 'Votre message a été envoyé avec succès !');
            return $this->redirectToRoute('contact');
        }

        return $this->render('contact/index.html.twig');
    }
}
