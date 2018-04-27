<?php

namespace App\Traits;

use Twig;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Solean\CleanProspecter\UseCase\UseCasesFacade;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

trait HelperTrait
{
    use ServiceTrait;

    public function setFormFactory(FormFactory $formFactory)
    {
        $this->add('form-factory', $formFactory);
    }

    public function setTwig(Twig\Environment $twig)
    {
        $this->add('twig', $twig);
    }

    public function setRouter(RouterInterface $router)
    {
        $this->add('router', $router);
    }

    public function setUseCases(UseCasesFacade $facade)
    {
        $this->add('use-cases', $facade);
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @see UrlGeneratorInterface
     *
     * @final
     */
    protected function generateUrl(string $route, array $parameters = array(), int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
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
    protected function redirectToRoute(string $route, array $parameters = array(), int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Returns a rendered view.
     *
     * @final
     */
    protected function renderView(string $view, array $parameters = array()): string
    {
        return $this->getTwig()->render($view, $parameters);
    }

    /**
     * Renders a view.
     *
     * @final
     */
    protected function render(string $view, array $parameters = array(), Response $response = null): Response
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
    protected function createForm(string $type, $data = null, array $options = array()): FormInterface
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

    protected function getFormFactory(): FormFactory
    {
        return $this->get('form-factory');
    }
}
