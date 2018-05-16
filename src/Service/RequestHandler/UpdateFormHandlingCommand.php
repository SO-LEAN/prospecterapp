<?php

namespace App\Service\RequestHandler;

use stdClass;
use App\Entity\User;
use App\Traits\HelperTrait;
use App\Service\FormHandlingCommand;
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
        $parts = explode('_', $request->get('_route'), 2);

        return $this->render(sprintf('page/%s/update.html.twig', $parts[0]), $viewParameters, $response);
    }

    /**
     * @param Request $request
     *
     * @return stdClass
     */
    private function buildGetUseCasePresenter(Request $request): object
    {
        $class = $this->deduceGetPresenterFQCN($request);

        return new $class();
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deduceGetPresenterFQCN(Request $request): string
    {
        return sprintf('%s\%sForUpdatePresenter', $this::PRESENTER_NAMESPACE, ucfirst($this->deduceGetUseCaseName($request)));
    }

    /**
     * @param Request $request
     *
     * @return stdClass
     */
    private function buildGetUseCaseRequest(Request $request): object
    {
        $class = $this->deduceGetRequestFQCN($request);

        return new $class($request->get('id'));
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function deduceGetRequestFQCN(Request $request): string
    {
        $useCase = ucfirst($this->deduceGetUseCaseName($request));

        return sprintf('%s\%s\%sRequest', $this::USE_CASES_NAMESPACE, $useCase, $useCase);
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deduceGetUseCaseName(Request $request): string
    {
        $parts = explode('_', $request->get('_route'), 2);

        $parts[0] = ucfirst($parts[0]);
        $parts[1] = 'get';

        return implode(array_reverse($parts));
    }
}
