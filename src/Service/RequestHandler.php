<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequestHandler
{
    /**
     * @var FormHandlingCommand[]
     */
    private $commands;

    /**
     * @param string $id
     */
    public function addCommand(FormHandlingCommand $command, $id = 'default')
    {
        $this->commands[$id] = $command;
    }

    public function execute(Request $request, User $user, array $parameters = []): Response
    {
        return $this->handleCommand($request, $user, $parameters);
    }

    private function hasCommand(string $id): bool
    {
        return array_key_exists($id, $this->commands);
    }

    private function handleCommand(Request $request, User $user, array $parameters = []): Response
    {
        return $this->handleForm($request, $user, $parameters);
    }

    private function handleForm(Request $request, User $user, array $parameters): Response
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

        return $command->renderFormView($request, ['form' => $form->createView()] + ['data' => $form->getData()] + $parameters);
    }

    private function selectCommand(Request $request): FormHandlingCommand
    {
        if ($this->hasCommand($request->get('_route'))) {
            return $this->getCommand($request->get('_route'));
        }

        return $this->getCommand($this->getRouteSuffix($request));
    }

    private function getCommand(string $id): FormHandlingCommand
    {
        if (!$this->hasCommand($id)) {
            throw new ServiceNotFoundException($id);
        }

        return $this->commands[$id];
    }

    private function getRouteSuffix(Request $request): string
    {
        $exploded = explode('_', $request->get('_route'));

        return end($exploded);
    }
}
