<?php
namespace App\Service;

use Exception;
use App\Entity\User;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface FormHandlingCommand
{
    /**
     * Initialize FormInterface
     *
     * @param Request $request
     * @param User $user
     * @return FormInterface
     */
    public function initializeForm(Request $request, User $user): FormInterface;

    /**
     * On form validation success
     *
     * @param array $data
     * @param Request $request
     * @param User $user
     *
     * @return Response
     */
    public function onSucceed(array $data, Request $request, User $user): Response;

    /**
     * On Use case exception
     *
     * @param Exception $e
     * @param FormInterface $form
     */
    public function onUseCaseException(Exception $e,  FormInterface $form): void;

    /**
     * Render the view
     *
     * @param Request $request
     * @param array $viewParameters
     * @param Response $response
     *
     * @return Response
     */
    public function renderFormView(Request $request, array $viewParameters, ?Response $response = null): Response;
}
