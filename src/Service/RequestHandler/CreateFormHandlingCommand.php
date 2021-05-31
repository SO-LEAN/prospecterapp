<?php

namespace App\Service\RequestHandler;

use App\Entity\User;
use App\Service\FormHandlingCommand;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateFormHandlingCommand extends AbstractFormHandlingCommand implements FormHandlingCommand
{
    /**
     * {@inheritdoc}
     */
    public function initializeForm(Request $request, User $user): FormInterface
    {
        return $this->getFormFactory()->create($this->deduceFormFQCN($request));
    }

    /**
     * {@inheritdoc}
     */
    public function renderFormView(Request $request, array $viewParameters, ?Response $response = null): Response
    {
        $parts = explode('_', $request->get('_route'), 2);

        return $this->render(sprintf('page/%s/add.html.twig', $parts[0]), $viewParameters, $response);
    }
}
