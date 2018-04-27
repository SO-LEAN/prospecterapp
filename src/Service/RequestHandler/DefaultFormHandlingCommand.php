<?php

namespace App\Service\RequestHandler;

use stdClass;
use Exception;
use ReflectionClass;
use App\Entity\User;
use App\Traits\HelperTrait;
use App\Service\FormHandlingCommand;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Solean\CleanProspecter\UseCase\Presenter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Solean\CleanProspecter\Exception\UseCase\UseCaseException;

class DefaultFormHandlingCommand implements FormHandlingCommand
{
    use HelperTrait;

    const FORMS_NAMESPACE = 'App\Form';
    const PRESENTER_NAMESPACE = 'App\Presenter';
    const USE_CASES_NAMESPACE = 'Solean\CleanProspecter\UseCase';

    /**
     * {@inheritdoc}
     */
    public function initializeForm(Request $request, User $user): FormInterface
    {
        return $this->getFormFactory()->create($this->deduceFormFQCN($request));
    }

    /**
     * {@inheritdoc}
     */
    public function onSucceed(array $data, Request $request, User $user): Response
    {
        $method = $this->deduceUseCaseName($request);
        $useCaseRequest = $this->buildUseCaseRequest($request, $data, $user);
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
     * {@inheritdoc}
     */
    public function renderFormView(Request $request, array $viewParameters, ?Response $response = null): Response
    {
        return $this->render($this->deduceTargetTemplate($request), $viewParameters, $response);
    }

    /**
     * @param Request $request
     * @param array   $data
     * @param User    $user
     *
     * @return stdClass
     *
     * @throws \ReflectionException
     */
    private function buildUseCaseRequest(Request $request, array $data, User $user): object
    {
        $class = $this->deduceRequestFQCN($request);

        $args = iterator_to_array($this->constructArgs($class, $data));
        $args = $this->fillUserData($user, $args);

        $objectReflection = new ReflectionClass($class);
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
    private function constructArgs(string $class, array $data): \Generator
    {
        $reflection = new ReflectionClass($class);

        foreach ($reflection->getConstructor()->getParameters() as $param) {
            (yield $param->name => isset($data[$param->name]) ? $data[$param->name] : null);
        }
    }

    /**
     * @param User $user
     * @param $params
     *
     * @return array
     */
    private function fillUserData(User $user, $params): array
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
    private function deduceFormFQCN(Request $request): string
    {
        return sprintf('%s\%sForm', $this::FORMS_NAMESPACE, ucfirst($this->deduceUseCaseName($request)));
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deduceRequestFQCN(Request $request): string
    {
        $useCase = ucfirst($this->deduceUseCaseName($request));

        return sprintf('%s\%s\%sRequest', $this::USE_CASES_NAMESPACE, $useCase, $useCase);
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

    /**
     * @param Request $request
     *
     * @return string
     */
    private function deduceTargetTemplate(Request $request): string
    {
        $parts = explode('_', $request->get('_route'), 2);

        if ('create' === $parts[1]) {
            $parts[1] = 'add';
        } else {
            $parts[1] = 'update';
        }

        return sprintf('page/%s.html.twig', implode('-', $parts));
    }
}
