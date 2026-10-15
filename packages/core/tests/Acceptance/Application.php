<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Application\Account\AccountResponse;
use Prospecter\Core\Application\Account\ChangeMyContactDetails;
use Prospecter\Core\Application\Account\ChangeMyContactDetailsRequest;
use Prospecter\Core\Application\Account\ChangeMyName;
use Prospecter\Core\Application\Account\ChangeMyNameRequest;
use Prospecter\Core\Application\Account\ChangeMyOrganizationLogo;
use Prospecter\Core\Application\Account\ChangeMyOrganizationLogoRequest;
use Prospecter\Core\Application\Account\ChangeMyPicture;
use Prospecter\Core\Application\Account\ChangeMyPictureRequest;
use Prospecter\Core\Application\Account\ChangeMyUserName;
use Prospecter\Core\Application\Account\ChangeMyUserNameRequest;
use Prospecter\Core\Application\Account\ConsultMyAccount;
use Prospecter\Core\Application\Account\ConsultMyAccountRequest;
use Prospecter\Core\Application\Account\ReviseMyOrganizationIdentity;
use Prospecter\Core\Application\Account\ReviseMyOrganizationIdentityRequest;
use Prospecter\Core\Application\Organization\AttachToHolding;
use Prospecter\Core\Application\Organization\AttachToHoldingRequest;
use Prospecter\Core\Application\Organization\ChangeContactDetails;
use Prospecter\Core\Application\Organization\ChangeContactDetailsRequest;
use Prospecter\Core\Application\Organization\ChangeLogo;
use Prospecter\Core\Application\Organization\ChangeLogoRequest;
use Prospecter\Core\Application\Organization\ConsultOrganization;
use Prospecter\Core\Application\Organization\ConsultOrganizationRequest;
use Prospecter\Core\Application\Organization\DetachFromHolding;
use Prospecter\Core\Application\Organization\DetachFromHoldingRequest;
use Prospecter\Core\Application\Organization\OrganizationResponse;
use Prospecter\Core\Application\Organization\Relocate;
use Prospecter\Core\Application\Organization\RelocateRequest;
use Prospecter\Core\Application\Organization\RemoveLogo;
use Prospecter\Core\Application\Organization\RemoveLogoRequest;
use Prospecter\Core\Application\Organization\ReviseLegalIdentity;
use Prospecter\Core\Application\Organization\ReviseLegalIdentityRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizationsRequest;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizationsResponse;
use Prospecter\Core\Application\Organization\StartProspecting;
use Prospecter\Core\Application\Organization\StartProspectingRequest;
use Prospecter\Core\Application\Organization\WriteObservations;
use Prospecter\Core\Application\Organization\WriteObservationsRequest;
use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\NotFound;
use Prospecter\Core\Domain\User\UserId;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryOrganizationRepository;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\InMemoryUserRepository;
use Tests\Core\Double\KnownPlacesGeoLocation;
use Tests\Core\Double\LastResponsePresenter;

/**
 * The hexagon as the scenarios use it: the real use cases, wired to in-memory implementations of their
 * ports, called the way a delivery calls them. It is the only place that knows how a use case is invoked
 * (today execute(), tomorrow a command sent to a bus).
 */
final class Application
{
    public readonly InMemoryOrganizationRepository $organizations;
    public readonly InMemoryUserRepository $users;
    public readonly InMemoryStorage $storage;
    public readonly KnownPlacesGeoLocation $geoLocation;

    private ?UserId $signedIn = null;
    private NotFound|BusinessRuleViolation|null $refusal = null;

    public function __construct()
    {
        $database = new InMemoryDatabase();
        $this->organizations = new InMemoryOrganizationRepository($database);
        $this->users = new InMemoryUserRepository($database);
        $this->storage = new InMemoryStorage();
        $this->geoLocation = new KnownPlacesGeoLocation();
    }

    public function signIn(UserId $user): void
    {
        $this->signedIn = $user;
    }

    public function signedIn(): UserId
    {
        return $this->signedIn ?? throw new \LogicException('Nobody is signed in: add "je suis connecté..." to the scenario');
    }

    public function startProspecting(StartProspectingRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new StartProspecting($this->organizations, $this->users, $this->geoLocation), $request);
    }

    public function reviseLegalIdentity(ReviseLegalIdentityRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new ReviseLegalIdentity($this->organizations, $this->users), $request);
    }

    public function changeContactDetails(ChangeContactDetailsRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new ChangeContactDetails($this->organizations, $this->users), $request);
    }

    public function relocate(RelocateRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new Relocate($this->organizations, $this->users, $this->geoLocation), $request);
    }

    public function writeObservations(WriteObservationsRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new WriteObservations($this->organizations, $this->users), $request);
    }

    public function attachToHolding(AttachToHoldingRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new AttachToHolding($this->organizations, $this->users), $request);
    }

    public function detachFromHolding(DetachFromHoldingRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new DetachFromHolding($this->organizations, $this->users), $request);
    }

    public function changeLogo(ChangeLogoRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new ChangeLogo($this->organizations, $this->users, $this->storage), $request);
    }

    public function removeLogo(RemoveLogoRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new RemoveLogo($this->organizations, $this->users, $this->storage), $request);
    }

    public function consultOrganization(ConsultOrganizationRequest $request): ?OrganizationResponse
    {
        return $this->organizationIntent(new ConsultOrganization($this->organizations, $this->users), $request);
    }

    public function searchProspectedOrganizations(SearchProspectedOrganizationsRequest $request): ?SearchProspectedOrganizationsResponse
    {
        return $this->attempt(new SearchProspectedOrganizations($this->organizations, $this->users), $request, SearchProspectedOrganizationsResponse::class);
    }

    public function consultMyAccount(): ?AccountResponse
    {
        return $this->accountIntent(new ConsultMyAccount($this->users, $this->organizations), new ConsultMyAccountRequest($this->signedIn()));
    }

    public function changeMyName(ChangeMyNameRequest $request): ?AccountResponse
    {
        return $this->accountIntent(new ChangeMyName($this->users, $this->organizations), $request);
    }

    public function changeMyUserName(ChangeMyUserNameRequest $request): ?AccountResponse
    {
        return $this->accountIntent(new ChangeMyUserName($this->users, $this->organizations), $request);
    }

    public function changeMyContactDetails(ChangeMyContactDetailsRequest $request): ?AccountResponse
    {
        return $this->accountIntent(new ChangeMyContactDetails($this->users, $this->organizations), $request);
    }

    public function changeMyPicture(ChangeMyPictureRequest $request): ?AccountResponse
    {
        return $this->accountIntent(new ChangeMyPicture($this->users, $this->organizations, $this->storage), $request);
    }

    public function reviseMyOrganizationIdentity(ReviseMyOrganizationIdentityRequest $request): ?AccountResponse
    {
        return $this->accountIntent(new ReviseMyOrganizationIdentity($this->users, $this->organizations), $request);
    }

    public function changeMyOrganizationLogo(ChangeMyOrganizationLogoRequest $request): ?AccountResponse
    {
        return $this->accountIntent(new ChangeMyOrganizationLogo($this->users, $this->organizations, $this->storage), $request);
    }

    /**
     * The business refusal of the last use case, null when it succeeded.
     */
    public function refusal(): NotFound|BusinessRuleViolation|null
    {
        return $this->refusal;
    }

    private function organizationIntent(object $useCase, object $request): ?OrganizationResponse
    {
        return $this->attempt($useCase, $request, OrganizationResponse::class);
    }

    private function accountIntent(object $useCase, object $request): ?AccountResponse
    {
        return $this->attempt($useCase, $request, AccountResponse::class);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $responseClass
     *
     * @return T|null
     */
    private function attempt(object $useCase, object $request, string $responseClass): ?object
    {
        $this->refusal = null;
        $presenter = new LastResponsePresenter();

        try {
            [$useCase, 'execute']($request, $presenter);
        } catch (NotFound|BusinessRuleViolation $refusal) {
            $this->refusal = $refusal;

            return null;
        }

        return $presenter->response($responseClass);
    }
}
