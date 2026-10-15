<?php

namespace App\Security;

use App\Auth\Passwords;
use App\Form\LoginType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ProspecterAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly ProspecterUserProvider $users,
        private readonly Passwords $passwords,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $loginRoute,
        private readonly string $defaultSuccessUrl,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(LoginType::class, null, ['csrf_protection' => false]);
        $form->handleRequest($request);
        $data = $form->getData() ?? [];
        $loginEmail = (string) ($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $csrfToken = (string) ($request->request->all($form->getName())['_token'] ?? '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $loginEmail);

        return new Passport(
            new UserBadge($loginEmail),
            new CustomCredentials(fn (): bool => $this->checkCredentials($loginEmail, $password), null),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $path = $this->getTargetPath($request->getSession(), $firewallName);

        if (!$path || $this->getLoginUrl($request) === $path) {
            $path = $this->defaultSuccessUrl;
        }

        return new RedirectResponse($path);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate($this->loginRoute);
    }

    private function checkCredentials(string $loginEmail, string $password): bool
    {
        if (!$this->isPasswordOf($loginEmail, $password)) {
            throw new BadCredentialsException('Bad credentials');
        }

        return true;
    }

    private function isPasswordOf(string $loginEmail, string $password): bool
    {
        try {
            return $this->passwords->verify($this->users->find($loginEmail)->id(), $password);
        } catch (UserNotFoundException) {
            return false;
        }
    }
}
