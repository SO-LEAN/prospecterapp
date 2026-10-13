<?php

namespace App\Service\RequestHandler;

use App\Entity\User;
use App\Service\UploadedFileInfo;
use App\Traits\HelperTrait;
use Solean\CleanProspecter\Exception\UseCase\UseCaseException;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFormHandlingCommand
{
    use HelperTrait;

    public const FORMS_NAMESPACE = 'App\Form\UseCaseType';
    public const PRESENTER_NAMESPACE = 'App\Presenter';
    public const USE_CASES_NAMESPACE = 'Solean\CleanProspecter\UseCase';

    public function onSucceed(array $data, Request $request, User $user): ?Response
    {
        $method = $this->deduceUseCaseName($request);
        $useCaseRequest = $this->buildUseCaseRequest($this->deduceRequestFQCN($request), $data, $user);
        $presenter = $this->buildPresenter($request);

        $response = $this->getUseCases()->$method(
            $useCaseRequest,
            $presenter,
            $user
        );

        if (!$this->getRouter()->getRouteCollection()->get($this->deduceTargetRoute($request))) {
            return $this->redirectToRoute($request->attributes->get('_route'), $request->query->all());
        }

        return $this->redirectToRoute($this->deduceTargetRoute($request), [
            'id' => $response->getId(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function onUseCaseException(\Exception $e, FormInterface $form): void
    {
        if ($e instanceof UseCaseException) {
            foreach ($e->getRequestErrors() as $key => $msg) {
                if ('*' === $key) {
                    $form->addError(new FormError($msg));
                } else {
                    $form->get($key)->addError(new FormError($msg));
                }
            }
        } else {
            throw $e;
        }
    }

    protected function buildUseCaseRequest(string $useCaseClass, array $data, User $user): object
    {
        $args = iterator_to_array($this->constructArgs($useCaseClass, $data));
        $args = $this->fillUserData($user, $args);

        $objectReflection = new \ReflectionClass($useCaseClass);

        return $objectReflection->newInstanceArgs($args);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function constructArgs(string $class, array $data): \Generator
    {
        $reflection = new \ReflectionClass($class);

        foreach ($reflection->getConstructor()->getParameters() as $param) {
            $value = $data[$param->name] ?? null;

            yield $param->name => $value instanceof UploadedFile ? new UploadedFileInfo($value) : $value;
        }
    }

    protected function deduceFormFQCN(Request $request): string
    {
        return sprintf('%s\%sType', $this::FORMS_NAMESPACE, ucfirst($this->deduceUseCaseName($request)));
    }

    protected function deduceRequestFQCN(Request $request): string
    {
        $useCase = ucfirst($this->deduceUseCaseName($request));

        return sprintf('%s\%s\%sRequest', $this::USE_CASES_NAMESPACE, $useCase, $useCase);
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function fillUserData(User $user, array $params): array
    {
        if (array_key_exists('ownedBy', $params)) {
            $params['ownedBy'] = $user->getOrganizationId();
        }

        return $params;
    }

    private function buildPresenter(Request $request): object
    {
        $class = $this->deducePresenterFQCN($request);

        return new $class();
    }

    private function deduceUseCaseName(Request $request): string
    {
        $words = ucwords($request->attributes->get('_route'), '_');
        $parts = explode('_', $words);

        $action = array_pop($parts);
        array_unshift($parts, strtolower($action));

        return implode('', $parts);
    }

    private function deducePresenterFQCN(Request $request): string
    {
        return sprintf('%s\%sPresenterImpl', $this::PRESENTER_NAMESPACE, ucfirst($this->deduceUseCaseName($request)));
    }

    private function deduceTargetRoute(Request $request): string
    {
        $parts = explode('_', $request->attributes->get('_route'), 2);
        $parts[1] = 'view';

        return implode('_', $parts);
    }
}
