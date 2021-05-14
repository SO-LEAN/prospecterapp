<?php

namespace App\Service\RequestHandler;

use App\Entity\User;
use App\Service\FormHandlingCommand;
use App\Traits\HelperTrait;
use stdClass;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateFormHandlingCommand extends AbstractFormHandlingCommand implements FormHandlingCommand
{
    use HelperTrait;

    /**
     * {@inheritdoc}
     */
    public function initializeForm(Request $request, User $user): FormInterface
    {
        $method = $this->deduceGetUseCaseName($request);
        $useCaseRequest = $this->buildGetUseCaseRequest($request);
        $presenter = $this->buildGetUseCasePresenter($request);

        $data = $this->getUseCases()->$method($useCaseRequest, $presenter, $user);

        return $this->getFormFactory()->create($this->deduceFormFQCN($request), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function renderFormView(Request $request, array $viewParameters, ?Response $response = null): Response
    {
        $parts = explode('_', $request->get('_route'));
        array_pop($parts);

        return $this->render(sprintf('page/%s/update.html.twig', implode('-', $parts)), $viewParameters, $response);
    }

    /**
     * @return stdClass
     */
    private function buildGetUseCasePresenter(Request $request): object
    {
        $class = $this->deduceGetPresenterFQCN($request);

        return new $class();
    }

    private function deduceGetPresenterFQCN(Request $request): string
    {
        return sprintf('%s\%sForUpdatePresenter', $this::PRESENTER_NAMESPACE, ucfirst($this->deduceGetUseCaseName($request)));
    }

    /**
     * @return stdClass
     */
    private function buildGetUseCaseRequest(Request $request): object
    {
        $class = $this->deduceGetRequestFQCN($request);

        return new $class($request->get('id'));
    }

    protected function deduceGetRequestFQCN(Request $request): string
    {
        $useCase = ucfirst($this->deduceGetUseCaseName($request));

        return sprintf('%s\%s\%sRequest', $this::USE_CASES_NAMESPACE, $useCase, $useCase);
    }

    private function deduceGetUseCaseName(Request $request): string
    {
        $words = ucwords($request->get('_route'), '_');
        $parts = explode('_', $words);
        array_pop($parts);
        array_unshift($parts, 'get');

        return implode('', $parts);
    }
}
