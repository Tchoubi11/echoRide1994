<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LegalMentionsController extends AbstractController
{
    #[Route('/legal-mentions', name: 'legal_mentions')]
    public function index(): Response
    {
        
        return $this->render('legal_mentions/mentions.html.twig');
    }
}
