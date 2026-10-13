<?php

namespace App\Security;

use App\Form\UseCaseType\LoginType;
use App\Presenter\LoginPresenterImpl;
use Prospecter\Core\Exception\UseCase\BadCredentialException;
use Prospecter\Core\UseCase\Login\LoginRequest;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
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
        private readonly UseCasesFacade $useCasesFacade,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $loginRoute,
        private readonly string $defaultSuccessRedirectRoute,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $form = $this->formFactory->create(LoginType::class, null, ['csrf_protection' => false]);
        $form->handleRequest($request);
        $data = $form->getData() ?? [];
        $userName = (string) ($data['userName'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $csrfToken = (string) ($request->request->all($form->getName())['_token'] ?? '');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $userName);

        return new Passport(
            new UserBadge($userName),
            new CustomCredentials(fn (): bool => $this->checkCredentials($userName, $password), null),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $path = $this->getTargetPath($request->getSession(), $firewallName);

        if (!$path || $this->getLoginUrl($request) === $path) {
            $path = $this->urlGenerator->generate($this->defaultSuccessRedirectRoute);
        }

        return new RedirectResponse($path);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate($this->loginRoute);
    }

    private function checkCredentials(string $userName, string $password): bool
    {
        try {
            $this->useCasesFacade->login(new LoginRequest($userName, $password), new LoginPresenterImpl());
        } catch (BadCredentialException) {
            throw new BadCredentialsException('Bad credentials');
        }

        return true;
    }
}
