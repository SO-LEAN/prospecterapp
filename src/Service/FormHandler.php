<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormHandler
{
    /**
     * @var FormHandlingCommand[]
     */
    private $commands;

    /**
     * @param FormHandlingCommand $command
     * @param string $id
     */
    public function addCommand(FormHandlingCommand $command, $id = 'default')
    {
        $this->commands[$id] = $command;
    }

    /**
     * @param array $parameters
     * @param Request $request
     * @param User $user
     * @return Response
     */
    public function execute(array $parameters, Request $request, User $user): Response
    {
        return $this->handleCommand($parameters, $request, $user);
    }

    /**
     * @param string $id
     * @return bool
     */
    private function hasCommand(string $id): bool
    {
        return array_key_exists($id, $this->commands);
    }

    /**
     * @param string $id
     * @return FormHandlingCommand
     */
    private function getCommand(string $id): FormHandlingCommand
    {
        if (!$this->hasCommand($id)) {
            throw new ServiceNotFoundException($id);
        }

        return $this->commands[$id];
    }

    /**
     * @param Request $request
     * @return FormHandlingCommand
     */
    private function selectCommand(Request $request): FormHandlingCommand
    {
        if ($this->hasCommand($request->get('_route'))) {
            return $this->getCommand($request->get('_route'));
        }

        return $this->getCommand('default');
    }

    /**
     * @param array $parameters
     * @param Request $request
     * @param User $user
     * @return Response
     */
    private function handleCommand(array $parameters, Request $request, User $user) : Response
    {
        $command = $this->selectCommand($request);
        $form = $command->initializeForm($request, $user);

        $form->handleRequest($request);
        try {
            if ($form->isSubmitted() && $form->isValid()) {
                return $command->onSucceed($form->getData(), $request, $user);
            }
        } catch (\Exception $e) {
            $command->onUseCaseException($e, $form);

        }

        return $command->renderFormView($request, ['form' => $form->createView()] + $parameters);
    }

}
