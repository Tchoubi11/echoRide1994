<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CookieController extends AbstractController
{
    #[Route('/accept-cookie', name: 'accept_cookie', methods: ['POST'])]
    public function acceptCookie(Request $request): Response
    {
        // Création d’un cookie "user_cookie_consent" valable 1 an
        $response = new Response('Cookie accepté');
        $cookie = Cookie::create('user_cookie_consent')
            ->withValue('accepted')
            ->withExpires(new \DateTime('+1 year'))
            ->withPath('/')
            ->withSecure(false) 
            ->withHttpOnly(false);

        $response->headers->setCookie($cookie);

        return $response;
    }
}
