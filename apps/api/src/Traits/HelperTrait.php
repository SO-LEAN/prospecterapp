<?php

namespace App\Traits;

use App\Service\PresenterFactory;
use Prospecter\Core\UseCase\UseCasesFacade;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

trait HelperTrait
{
    use ServiceTrait;

    public function setFormFactory(FormFactoryInterface $formFactory): void
    {
        $this->add('form-factory', $formFactory);
    }

    public function setTwig(Environment $twig): void
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

    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->getRouter()->generate($route, $parameters, $referenceType);
    }

    protected function redirect(string $url, int $status = 302): RedirectResponse
    {
        return new RedirectResponse($url, $status);
    }

    protected function redirectToRoute(string $route, array $parameters = [], int $status = 302): RedirectResponse
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    protected function renderView(string $view, array $parameters = []): string
    {
        return $this->getTwig()->render($view, $parameters);
    }

    protected function render(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $content = $this->getTwig()->render($view, $parameters);

        $response ??= new Response();

        $response->setContent($content);

        return $response;
    }

    protected function createForm(string $type, mixed $data = null, array $options = []): FormInterface
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

    protected function getTwig(): Environment
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
