<?php

declare(strict_types=1);

namespace Prospecter\Core\Application;

use Prospecter\Core\Application\Account\ChangeMyContactDetails\ChangeMyContactDetailsRequest;
use Prospecter\Core\Application\Account\ChangeMyName\ChangeMyNameRequest;
use Prospecter\Core\Application\Account\ChangeMyOrganizationLogo\ChangeMyOrganizationLogoRequest;
use Prospecter\Core\Application\Account\ChangeMyPicture\ChangeMyPictureRequest;
use Prospecter\Core\Application\Account\ChangeMyUserName\ChangeMyUserNameRequest;
use Prospecter\Core\Application\Account\ConsultMyAccount\ConsultMyAccountRequest;
use Prospecter\Core\Application\Account\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentityRequest;
use Prospecter\Core\Application\Organization\AttachToHolding\AttachToHoldingRequest;
use Prospecter\Core\Application\Organization\ChangeContactDetails\ChangeContactDetailsRequest;
use Prospecter\Core\Application\Organization\ChangeLogo\ChangeLogoRequest;
use Prospecter\Core\Application\Organization\ConsultOrganization\ConsultOrganizationRequest;
use Prospecter\Core\Application\Organization\DetachFromHolding\DetachFromHoldingRequest;
use Prospecter\Core\Application\Organization\Relocate\RelocateRequest;
use Prospecter\Core\Application\Organization\RemoveLogo\RemoveLogoRequest;
use Prospecter\Core\Application\Organization\ReviseLegalIdentity\ReviseLegalIdentityRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsPresenter;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsRequest;
use Prospecter\Core\Application\Organization\StartProspecting\StartProspectingRequest;
use Prospecter\Core\Application\Organization\WriteObservations\WriteObservationsRequest;

/**
 * Calls a use case by its name (lcfirst of its class name).
 * To be replaced by a command bus (ADR 0020).
 *
 * @method mixed startProspecting(StartProspectingRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed reviseLegalIdentity(ReviseLegalIdentityRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed changeContactDetails(ChangeContactDetailsRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed relocate(RelocateRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed writeObservations(WriteObservationsRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed attachToHolding(AttachToHoldingRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed detachFromHolding(DetachFromHoldingRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed changeLogo(ChangeLogoRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed removeLogo(RemoveLogoRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed consultOrganization(ConsultOrganizationRequest $request, Organization\OrganizationPresenter $presenter)
 * @method mixed searchProspectedOrganizations(SearchProspectedOrganizationsRequest $request, SearchProspectedOrganizationsPresenter $presenter)
 * @method mixed consultMyAccount(ConsultMyAccountRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyName(ChangeMyNameRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyUserName(ChangeMyUserNameRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyContactDetails(ChangeMyContactDetailsRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyPicture(ChangeMyPictureRequest $request, Account\AccountPresenter $presenter)
 * @method mixed reviseMyOrganizationIdentity(ReviseMyOrganizationIdentityRequest $request, Account\AccountPresenter $presenter)
 * @method mixed changeMyOrganizationLogo(ChangeMyOrganizationLogoRequest $request, Account\AccountPresenter $presenter)
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
