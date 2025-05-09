<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\Session\Session;

class LoginEntryPoint implements AuthenticationEntryPointInterface
{
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function start(Request $request, ?AuthenticationException $authException = null): RedirectResponse
    {
        /** @var Session $session */
        $session = $request->getSession();

        if ($session !== null) {
            if (!$session->isStarted()) {
                $session->start();
            }

            $session->getFlashBag()->add('error', 'Veuillez vous connecter pour continuer.');
        }

        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
