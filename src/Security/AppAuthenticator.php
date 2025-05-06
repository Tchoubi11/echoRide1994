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
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\HttpFoundation\Session\Session;


class AppAuthenticator extends AbstractAuthenticator
{
    private UrlGeneratorInterface $urlGenerator;

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
        $email = trim($request->request->get('email'));
        $password = $request->request->get('password');

        if (!$email) {
            throw new CustomUserMessageAuthenticationException('Le champ email est requis pour l\'authentification.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse
{
    $user = $token->getUser();
    $roles = $user->getRoles();

    if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_EMPLOYE', $roles, true)) {
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    return new RedirectResponse($this->urlGenerator->generate('covoiturage_list'));
}

   
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?RedirectResponse
    {
        $session = $request->getSession();
    
        if ($session instanceof Session) {
            $session->getFlashBag()->add('error', $exception->getMessage());
        }
    
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }

}
