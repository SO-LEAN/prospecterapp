<?php

namespace App\Service\RequestHandler;

use App\Entity\User;
use App\Traits\HelperTrait;
use Exception;
use ReflectionClass;
use Solean\CleanProspecter\Exception\UseCase\UseCaseException;
use stdClass;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFormHandlingCommand
{
    use HelperTrait;

    const FORMS_NAMESPACE = 'App\Form\UseCaseType';
    const PRESENTER_NAMESPACE = 'App\Presenter';
    const USE_CASES_NAMESPACE = 'Solean\CleanProspecter\UseCase';

    /**
     * @throws \ReflectionException
     */
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
            return $this->redirectToRoute($request->get('_route'), $request->query->all());
        }

        return $this->redirectToRoute($this->deduceTargetRoute($request), [
            'id' => $response->getId(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function onUseCaseException(Exception $e, FormInterface $form): void
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

    /**
     * @return stdClass
     *
     * @throws \ReflectionException
     */
    protected function buildUseCaseRequest(string $useCaseClass, array $data, User $user): object
    {
        $args = iterator_to_array($this->constructArgs($useCaseClass, $data));
        $args = $this->fillUserData($user, $args);

        $objectReflection = new ReflectionClass($useCaseClass);
        $object = $objectReflection->newInstanceArgs($args);
        /* @var stdClass $object */

        return $object;
    }

    /**
     * @throws \ReflectionException
     */
    protected function constructArgs(string $class, array $data): \Generator
    {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getConstructor()->getParameters() as $param) {
            (yield $param->name => isset($data[$param->name]) ? $data[$param->name] : null);
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
     * @param $params
     */
    protected function fillUserData(User $user, $params): array
    {
        if (array_key_exists('ownedBy', $params)) {
            $params['ownedBy'] = $user->getOrganizationId();
        }

        return $params;
    }

    /**
     * @return mixed
     */
    private function buildPresenter(Request $request)
    {
        $class = $this->deducePresenterFQCN($request);

        return new $class();
    }

    private function deduceUseCaseName(Request $request): string
    {
        $words = ucwords($request->get('_route'), '_');
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
        $parts = explode('_', $request->get('_route'), 2);
        $parts[1] = 'view';

        return implode('_', $parts);
    }
}
