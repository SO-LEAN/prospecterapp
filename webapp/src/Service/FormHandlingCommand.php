<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface FormHandlingCommand
{
    public function initializeForm(Request $request, User $user): FormInterface;

    /**
     * @param array<string, mixed> $data
     */
    public function onSucceed(array $data, Request $request, User $user): ?Response;

    public function onUseCaseException(\Exception $e, FormInterface $form): void;

    /**
     * @param array<string, mixed> $viewParameters
     */
    public function renderFormView(Request $request, array $viewParameters, ?Response $response = null): Response;
}
