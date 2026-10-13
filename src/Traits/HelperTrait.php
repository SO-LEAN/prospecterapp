<?php

namespace App\Traits;

use App\Service\PresenterFactory;
use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig;

trait HelperTrait
{
    use ServiceTrait;

    public function setFormFactory(FormFactoryInterface $formFactory): void
    {
        $this->add('form-factory', $formFactory);
    }

    public function setTwig(Twig\Environment $twig): void
    {
        $this->add('twig', $twig);
    }

    public function setRouter(RouterInterface $router): void
    {
        $this->add('router', $router);
    }

    public function setUseCases(UseCasesFacade $facade): void
    {
        $this->add('use-cases', $facade);
    }

    public function setPresenterFactory(PresenterFactory $presenterFactory): void
    {
        $this->add('presenters', $presenterFactory);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see UrlGeneratorInterface
     *
     * @final
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->getRouter()->generate($route, $parameters, $referenceType);
    }

    /**
     * Returns a RedirectResponse to the given URL.
     *
     * @final
     */
    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @final
     */
    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Returns a rendered view.
     *
     * @final
     */
    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->getTwig()->render($view, $parameters);
    }

    /**
     * Renders a view.
     *
     * @final
     */
    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $content = $this->getTwig()->render($view, $parameters);

        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($content);

        return $response;
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @final
     */
    protected function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->getFormFactory()->create($type, $data, $options);
    }

    protected function getRouter(): RouterInterface
    {
        return $this->get('router');
    }

    protected function getUseCases(): UseCasesFacade
    {
        return $this->get('use-cases');
    }

    protected function getTwig(): Twig\Environment
    {
        return $this->get('twig');
    }

    protected function getFormFactory(): FormFactoryInterface
    {
        return $this->get('form-factory');
    }

    protected function getPresenterFactory(): PresenterFactory
    {
        return $this->get('presenters');
    }
}
