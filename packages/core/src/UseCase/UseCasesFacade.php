<?php

declare(strict_types=1);

namespace Prospecter\Core\UseCase;

use Prospecter\Core\Exception\UseCase\UnauthorizedException;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationPresenter;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationRequest;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsPresenter;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationPresenter;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationPresenter;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoPresenter;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoRequest;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationPresenter;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationRequest;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationPresenter;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationRequest;

/**
 * Calls a use case by its name (lcfirst of the class without "Impl") after checking the roles of the consumer.
 *
 * @method mixed createOrganization(CreateOrganizationRequest $request, CreateOrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed getOrganization(GetOrganizationRequest $request, GetOrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed findMyOwnOrganizations(FindMyOwnOrganizationsRequest $request, FindMyOwnOrganizationsPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed updateOrganization(UpdateOrganizationRequest $request, UpdateOrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed removeOrganizationLogo(RemoveOrganizationLogoRequest $request, RemoveOrganizationLogoPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed updateMyAccountInformation(UpdateMyAccountInformationRequest $request, UpdateMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed getMyAccountInformation(GetMyAccountInformationRequest $request, GetMyAccountInformationPresenter $presenter, UseCaseConsumer $consumer)
 */
class UseCasesFacade
{
    private const string ADMIN = 'ROLE_ADMIN';

    /** @var array<string, AbstractUseCase> */
    private array $useCases = [];

    public function addUseCase(AbstractUseCase $useCase): void
    {
        $this->useCases[$this->nameOf($useCase)] = $useCase;
    }

    /**
     * @return array<string, AbstractUseCase>
     */
    public function getUseCases(): array
    {
        return $this->useCases;
    }

    public function hasUseCase(string $name): bool
    {
        return isset($this->useCases[$name]);
    }

    /**
     * @param list<mixed> $arguments request, presenter and, for a protected use case, the consumer
     */
    public function __call(string $name, array $arguments): mixed
    {
        $useCase = $this->useCases[$name] ?? throw new \BadFunctionCallException(sprintf('Call to undefined method %s::%s()', self::class, $name));
        $consumer = $this->consumerFrom($name, $arguments);
        $this->assertCanBeExecutedBy($useCase, $consumer);

        return [$useCase, 'execute'](...$arguments);
    }

    private function nameOf(AbstractUseCase $useCase): string
    {
        return lcfirst((string) preg_replace(['/^.*\\\\/', '/Impl$/'], '', $useCase::class));
    }

    /**
     * @param list<mixed> $arguments
     */
    private function consumerFrom(string $name, array $arguments): ?UseCaseConsumer
    {
        $consumer = $arguments[2] ?? null;

        if (null !== $consumer && !$consumer instanceof UseCaseConsumer) {
            throw new \BadFunctionCallException(sprintf('Argument 3 passed to %s must be an instance of %s, instance of %s given', $name, UseCaseConsumer::class, get_debug_type($consumer)));
        }

        return $consumer;
    }

    private function assertCanBeExecutedBy(AbstractUseCase $useCase, ?UseCaseConsumer $consumer): void
    {
        $allowedRoles = $useCase->canBeExecutedBy();
        $roles = $consumer?->getRoles() ?? [];

        if ([] === $allowedRoles || in_array(self::ADMIN, $roles, true) || [] !== array_intersect($roles, $allowedRoles)) {
            return;
        }

        throw new UnauthorizedException();
    }
}
