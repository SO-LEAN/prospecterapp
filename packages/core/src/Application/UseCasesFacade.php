<?php

declare(strict_types=1);

namespace Prospecter\Core\Application;

use Prospecter\Core\Application\Exception\UnauthorizedException;
use Prospecter\Core\Domain\User\Role;

/**
 * Calls a use case by its name (lcfirst of its class name) after checking the roles of the consumer.
 * To be replaced by a command bus (ADR 0020).
 *
 * @method mixed startProspecting(Organization\StartProspectingRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed reviseLegalIdentity(Organization\ReviseLegalIdentityRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeContactDetails(Organization\ChangeContactDetailsRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed relocate(Organization\RelocateRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed writeObservations(Organization\WriteObservationsRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed attachToHolding(Organization\AttachToHoldingRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed detachFromHolding(Organization\DetachFromHoldingRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeLogo(Organization\ChangeLogoRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed removeLogo(Organization\RemoveLogoRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed consultOrganization(Organization\ConsultOrganizationRequest $request, Organization\OrganizationPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed searchProspectedOrganizations(Organization\SearchProspectedOrganizationsRequest $request, Organization\SearchProspectedOrganizationsPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed consultMyAccount(Account\ConsultMyAccountRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeMyName(Account\ChangeMyNameRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeMyUserName(Account\ChangeMyUserNameRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeMyContactDetails(Account\ChangeMyContactDetailsRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeMyPicture(Account\ChangeMyPictureRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed reviseMyOrganizationIdentity(Account\ReviseMyOrganizationIdentityRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 * @method mixed changeMyOrganizationLogo(Account\ChangeMyOrganizationLogoRequest $request, Account\AccountPresenter $presenter, UseCaseConsumer $consumer)
 */
class UseCasesFacade
{
    /** @var array<string, UseCase> */
    private array $useCases = [];

    public function addUseCase(UseCase $useCase): void
    {
        $this->useCases[$this->nameOf($useCase)] = $useCase;
    }

    /**
     * @return array<string, UseCase>
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
        $this->assertCanBeExecutedBy($useCase, $this->consumerFrom($name, $arguments));

        return [$useCase, 'execute'](...$arguments);
    }

    private function nameOf(UseCase $useCase): string
    {
        return lcfirst((string) preg_replace('/^.*\\\\/', '', $useCase::class));
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

    private function assertCanBeExecutedBy(UseCase $useCase, ?UseCaseConsumer $consumer): void
    {
        $allowedRoles = array_map(static fn (Role $role): string => $role->value, $useCase->canBeExecutedBy());
        $roles = $consumer?->getRoles() ?? [];

        if ([] === $allowedRoles || in_array(Role::Admin->value, $roles, true) || [] !== array_intersect($roles, $allowedRoles)) {
            return;
        }

        throw new UnauthorizedException();
    }
}
