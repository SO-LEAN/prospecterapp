<?php

namespace App\Service\RequestHandler;


use stdClass;
use Exception;
use ReflectionClass;
use App\Entity\User;
use App\Traits\HelperTrait;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Solean\CleanProspecter\Exception\UseCase\UseCaseException;

abstract class AbstractFormHandlingCommand
{
    use HelperTrait;

    const FORMS_NAMESPACE = 'App\Form';
    const PRESENTER_NAMESPACE = 'App\Presenter';
    const USE_CASES_NAMESPACE = 'Solean\CleanProspecter\UseCase';

    /**
     * {@inheritdoc}
     */
    public function onSucceed(array $data, Request $request, User $user): Response
    {
        $method = $this->deduceUseCaseName($request);
        $useCaseRequest = $this->buildUseCaseRequest($this->deduceRequestFQCN($request), $data, $user);
        $presenter = $this->buildPresenter($request);

        $response = $this->getUseCases()->$method(
            $useCaseRequest,
            $presenter,
            $user
        );

        return $this->redirectToRoute($this->deduceTargetRoute($request), [
            'id' => $response->getId(),
        ]);
    }

    /**
     * {@inheritdoc}
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
     * @param string $useCaseClass
     * @param array   $data
     * @param User    $user
     *
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

        return  $object;
    }

    /**
     * @param string $class
     * @param array  $data
     *
     * @return \Generator
     *
     * @throws \ReflectionException
     */
    protected function constructArgs(string $class, array $data): \Generator
    {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getConstructor()->getParameters() as $param) {
            (yield $param->name => isset($data[$param->name]) ? $data[$param->name] : null);
        }
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function deduceFormFQCN(Request $request): string
    {
        return sprintf('%s\%sType', $this::FORMS_NAMESPACE, ucfirst($this->deduceUseCaseName($request)));
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    protected function deduceRequestFQCN(Request $request): string
    {
        $useCase = ucfirst($this->deduceUseCaseName($request));

        return sprintf('%s\%s\%sRequest', $this::USE_CASES_NAMESPACE, $useCase, $useCase);
    }

    /**
     * @param User $user
     * @param $params
     *
     * @return array
     */
    protected function fillUserData(User $user, $params): array
    {
        if (array_key_exists('ownedBy', $params)) {
            $params['ownedBy'] = $user->getOrganizationId();
        }

        return $params;
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    private function buildPresenter(Request $request)
    {
        $class = $this->deducePresenterFQCN($request);

        return new $class();
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deduceUseCaseName(Request $request): string
    {
        $parts = explode('_', $request->get('_route'), 2);
        $parts[0] = ucfirst($parts[0]);

        return implode(array_reverse($parts));
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deducePresenterFQCN(Request $request): string
    {
        return sprintf('%s\%sPresenterImpl', $this::PRESENTER_NAMESPACE, ucfirst($this->deduceUseCaseName($request)));
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deduceTargetRoute(Request $request): string
    {
        $parts = explode('_', $request->get('_route'), 2);
        $parts[1] = 'view';

        return implode('_', $parts);
    }
}
