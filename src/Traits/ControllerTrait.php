<?php

namespace App\Traits;
use App\Entity\User;
use App\Service\FormHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller trait is inspired by Symfony Controller Trait
 * without the dependency to the container.
 */
trait ControllerTrait
{
    use HelperTrait;

    public function setFormHandler(FormHandler $formHandler)
    {
        $this->add('form-handler', $formHandler);
    }

    protected function getFormHandler(): FormHandler
    {
        return $this->get('form-handler');
    }

    /**
     * @param array $parameters
     * @param Request $request
     * @param User $user
     *
     * @return Response
     */
    protected function handleForm(array $parameters, Request $request, User $user): Response
    {
        return $this->getFormHandler()->execute($parameters, $request, $user);
    }
}
