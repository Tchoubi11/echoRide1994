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
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
{
    dump('🚀 Authentification en cours...');
    
    $email = $request->request->get('email');
    $password = $request->request->get('password');

    dump(' Email : ', $email);
    dump(' Password reçu');

    return new Passport(
        new UserBadge($email), 
        new PasswordCredentials($password) 
    );
}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
{
    $session = $request->getSession();
    $targetUrl = $session->get('redirect_after_login', $this->urlGenerator->generate('app_home'));
    $session->remove('redirect_after_login'); 

    return new RedirectResponse($targetUrl);
}


    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        // on redirige l'utilisateur en cas d'échec d'authentification
        return new RedirectResponse($this->urlGenerator->generate('app_login')); 
    }
}
