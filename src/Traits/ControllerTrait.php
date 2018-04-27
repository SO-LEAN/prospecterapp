<?php

namespace App\Traits;

use App\Entity\User;
use App\Service\RequestHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller trait is inspired by Symfony Controller Trait
 * without the dependency to the container.
 */
trait ControllerTrait
{
    use HelperTrait;

    public function setFormHandler(RequestHandler $formHandler)
    {
        $this->add('form-handler', $formHandler);
    }

    protected function getFormHandler(): RequestHandler
    {
        return $this->get('form-handler');
    }

    /**
     * @param Request $request
     * @param User    $user
     * @param array   $parameters
     *
     * @return Response
     */
    protected function handleForm(Request $request, User $user, array $parameters = []): Response
    {
        return $this->getFormHandler()->execute($request, $user, $parameters);
    }
}
