<?php

declare(strict_types=1);

namespace Prospecter\Core\Application;

/**
 * Calls a use case by its name (lcfirst of its class name).
 * To be replaced by a command bus (ADR 0020).
 *
 * @method mixed startProspecting(Organization\StartProspectingRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed reviseLegalIdentity(Organization\ReviseLegalIdentityRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed changeContactDetails(Organization\ChangeContactDetailsRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed relocate(Organization\RelocateRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed writeObservations(Organization\WriteObservationsRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed attachToHolding(Organization\AttachToHoldingRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed detachFromHolding(Organization\DetachFromHoldingRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed changeLogo(Organization\ChangeLogoRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed removeLogo(Organization\RemoveLogoRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed consultOrganization(Organization\ConsultOrganizationRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed searchProspectedOrganizations(Organization\SearchProspectedOrganizationsRequest $request, Organization\SearchProspectedOrganizationsPresenter $presenter)
 * @method mixed consultMyAccount(Account\ConsultMyAccountRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyName(Account\ChangeMyNameRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyUserName(Account\ChangeMyUserNameRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyContactDetails(Account\ChangeMyContactDetailsRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyPicture(Account\ChangeMyPictureRequest $request, Account\AccountPresenter $presenter)
 * @method mixed reviseMyOrganizationIdentity(Account\ReviseMyOrganizationIdentityRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyOrganizationLogo(Account\ChangeMyOrganizationLogoRequest $request, Account\AccountPresenter $presenter)
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
     * @param list<mixed> $arguments request and presenter
     */
    public function __call(string $name, array $arguments): mixed
    {
        $useCase = $this->useCases[$name] ?? throw new \BadFunctionCallException(sprintf('Call to undefined method %s::%s()', self::class, $name));

        return [$useCase, 'execute'](...$arguments);
    }

    private function nameOf(UseCase $useCase): string
    {
        return lcfirst((string) preg_replace('/^.*\\\\/', '', $useCase::class));
    }
}
