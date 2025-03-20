<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AppAuthenticator extends AbstractAuthenticator
{
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        // Vérifions ici si l'authentification est supportée pour cette requête
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        // ici on récupère l'email et le mot de passe de la requête
        $email = $request->request->get('email');
        $password = $request->request->get('password');


        // Identifiant de l'utilisateur et mot de passe pour authentifier l'utilisateur
        return new Passport(
            new UserBadge($email), 
            new PasswordCredentials($password) 
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
    {
        // on redirige l'utilisateur après une authentification réussie
        return new RedirectResponse($this->urlGenerator->generate('app_home')); 
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        // on redirige l'utilisateur en cas d'échec d'authentification
        return new RedirectResponse($this->urlGenerator->generate('app_login')); 
    }
}
