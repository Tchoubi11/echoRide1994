<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $session = $request->getSession();

        if ($session instanceof Session) {
            $session->getFlashBag()->add(
                'error',
                'Identifiants invalides. Vérifiez votre mot de passe ou votre email.'
            );
        }

        return new RedirectResponse($this->router->generate('app_login'));
    }
}
