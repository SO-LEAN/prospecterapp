<?php

namespace App\Service\RequestHandler;

use App\Entity\User;
use App\Traits\HelperTrait;
use App\Service\FormHandlingCommand;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Presenter\GetOrganizationForUpdatePresenter;
use Solean\CleanProspecter\UseCase\GetOrganization\GetOrganizationRequest;

class UpdateFormHandlingCommand extends AbstractFormHandlingCommand implements FormHandlingCommand
{
    use HelperTrait;

    /**
     * {@inheritdoc}
     */
    public function initializeForm(Request $request, User $user): FormInterface
    {
        $data = $this->getUseCases()->getOrganization(new GetOrganizationRequest($request->get('id')), new GetOrganizationForUpdatePresenter(), $user);

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
}
