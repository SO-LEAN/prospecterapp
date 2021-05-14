<?php

namespace App\Service;

use App\Entity\User;
use Exception;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface FormHandlingCommand
{
    /**
     * Initialize FormInterface.
     */
    public function initializeForm(Request $request, User $user): FormInterface;

    /**
     * On form validation success.
     *
     * @return Response
     */
    public function onSucceed(array $data, Request $request, User $user): ?Response;

    /**
     * On Use case exception.
     */
    public function onUseCaseException(Exception $e, FormInterface $form): void;

    /**
     * Render the view.
     *
     * @param Response $response
     */
    public function renderFormView(Request $request, array $viewParameters, ?Response $response = null): Response;
}
