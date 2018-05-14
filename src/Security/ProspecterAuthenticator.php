<?php

namespace App\Security;

use App\Form\LoginType;
use App\Presenter\LoginPresenterImpl;
use Symfony\Component\Security\Core\Security;
use Solean\CleanProspecter\Exception\UseCase\BadCredentialException;
use Solean\CleanProspecter\UseCase\Login\LoginRequest;
use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ProspecterAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;
    /**
     * @var FormFactory
     */
    private $formFactory;
    /**
     * @var SessionInterface
     */
    private $session;
    /**
     * @var UseCasesFacade
     */
    private $useCasesFacade;
    /**
     * @var string
     */
    private $loginUrl;
    /**
     * @var string
     */
    private $defaultSuccessRedirectUrl;
    /**
     * @var string
     */
    private $providerKey;

    /**
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param FormFactoryInterface      $formFactory
     * @param SessionInterface          $session
     * @param UseCasesFacade            $useCasesFacade
     * @param UrlGeneratorInterface     $urlGenerator,
     * @param string                    $loginRoute
     * @param string                    $defaultSuccessRedirectRoute
     * @param string                    $providerKey
     */
    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        FormFactoryInterface $formFactory,
        SessionInterface $session,
        UseCasesFacade $useCasesFacade,
        UrlGeneratorInterface $urlGenerator,
        string $loginRoute,
        string $defaultSuccessRedirectRoute,
        string $providerKey)
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->formFactory = $formFactory;
        $this->session = $session;
        $this->useCasesFacade = $useCasesFacade;
        $this->loginUrl = $urlGenerator->generate($loginRoute);
        $this->defaultSuccessRedirectUrl = $urlGenerator->generate($defaultSuccessRedirectRoute);
        $this->providerKey = $providerKey;
        $this->session = $session;
    }

    /**
     * @param Request                      $request
     * @param AuthenticationException|null $authException
     *
     * @return Response|null
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $this->saveTargetPath($this->session, $this->providerKey, $request->getUri());

        return parent::start($request, $authException);
    }

    /**
     * @param Request $request
     *
     * @return LoginRequest
     */
    public function getCredentials(Request $request)
    {
        $form = $this->formFactory->create(LoginType::class);
        $form->handleRequest($request);
        $csrfToken = $request->get($form->getName())['_token'];

        if (false === $this->csrfTokenManager->isTokenValid(new CsrfToken('authenticate', $csrfToken))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        $data = $form->getData();

        $request->getSession()->set(Security::LAST_USERNAME, $data['userName']);

        return $data;
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $path = $this->getTargetPath($this->session, $this->providerKey);

        if (!$path || $this->loginUrl === $path) {
            $path = $this->getDefaultSuccessRedirectUrl();
        }

        return new RedirectResponse($path);
    }

    /**
     * @param mixed                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $userProvider->loadUserByUsername($credentials['userName']);
    }

    /**
     * @param mixed         $credentials
     * @param UserInterface $user
     *
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        try {
            $this->useCasesFacade->login(new LoginRequest($credentials['userName'], $credentials['password']), new LoginPresenterImpl());
        } catch (BadCredentialException $e) {
            throw new AuthenticationException('Bad credentials');
        }

        return true;
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->getPathInfo() === $this->getLoginUrl() && $request->isMethod('POST');
    }

    /**
     * @return string
     */
    protected function getLoginUrl()
    {
        return $this->loginUrl;
    }

    /**
     * @return string
     */
    protected function getDefaultSuccessRedirectUrl()
    {
        return $this->defaultSuccessRedirectUrl;
    }

    /**
     * @param SessionInterface $session
     * @param string           $providerKey
     * @param string           $url
     */
    private function saveTargetPath(SessionInterface $session, $providerKey, $url)
    {
        $session->set('_security.'.$providerKey.'.target_path', $url);
    }

    /**
     * @param SessionInterface $session
     * @param string           $providerKey
     *
     * @return mixed|string
     */
    private function getTargetPath(SessionInterface $session, $providerKey)
    {
        return $session->get('_security.'.$providerKey.'.target_path');
    }
}
