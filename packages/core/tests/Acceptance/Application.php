<?php

namespace Tests\Core\Acceptance;

use LogicException;
use Prospecter\Core\Exception\UseCase\UseCaseException;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationImpl;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationRequest;
use Prospecter\Core\UseCase\CreateOrganization\CreateOrganizationResponse;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsImpl;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsRequest;
use Prospecter\Core\UseCase\FindMyOwnOrganizations\FindMyOwnOrganizationsResponse;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationImpl;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationRequest;
use Prospecter\Core\UseCase\GetMyAccountInformation\GetMyAccountInformationResponse;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationImpl;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationRequest;
use Prospecter\Core\UseCase\GetOrganization\GetOrganizationResponse;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoImpl;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoRequest;
use Prospecter\Core\UseCase\RemoveOrganizationLogo\RemoveOrganizationLogoResponse;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationImpl;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationRequest;
use Prospecter\Core\UseCase\UpdateMyAccountInformation\UpdateMyAccountInformationResponse;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationImpl;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationRequest;
use Prospecter\Core\UseCase\UpdateOrganization\UpdateOrganizationResponse;
use Tests\Core\Double\CollectedNotifications;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryOrganizationGateway;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\InMemoryTransaction;
use Tests\Core\Double\InMemoryUserGateway;
use Tests\Core\Double\KnownPlacesGeoLocation;
use Tests\Core\Double\LastResponsePresenter;
use Tests\Core\Double\SignedInUser;

/**
 * The hexagon as the scenarios use it: the real use cases, wired to in-memory implementations of their
 * ports, called the way a delivery calls them. It is the only place that knows how a use case is
 * invoked (today `execute()`, tomorrow a command sent to a bus).
 */
final class Application
{
    public readonly InMemoryDatabase $database;
    public readonly InMemoryOrganizationGateway $organizations;
    public readonly InMemoryUserGateway $users;
    public readonly InMemoryStorage $storage;
    public readonly KnownPlacesGeoLocation $geoLocation;
    public readonly CollectedNotifications $notifications;
    private readonly InMemoryTransaction $transaction;

    private ?SignedInUser $signedIn = null;
    private ?UseCaseException $refusal = null;

    public function __construct()
    {
        $this->database = new InMemoryDatabase();
        $this->organizations = new InMemoryOrganizationGateway($this->database);
        $this->users = new InMemoryUserGateway($this->database);
        $this->storage = new InMemoryStorage();
        $this->geoLocation = new KnownPlacesGeoLocation();
        $this->notifications = new CollectedNotifications();
        $this->transaction = new InMemoryTransaction($this->database);
    }

    public function signIn(SignedInUser $user): void
    {
        $this->signedIn = $user;
    }

    public function signedIn(): SignedInUser
    {
        return $this->signedIn ?? throw new LogicException('Nobody is signed in: add "je suis connecté..." to the scenario');
    }

    public function createOrganization(CreateOrganizationRequest $request): ?CreateOrganizationResponse
    {
        $useCase = new CreateOrganizationImpl($this->organizations, $this->storage, $this->notifications, $this->geoLocation);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute($request, $presenter), CreateOrganizationResponse::class);
    }

    public function getOrganization(GetOrganizationRequest $request): ?GetOrganizationResponse
    {
        $useCase = new GetOrganizationImpl($this->organizations);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute($request, $presenter), GetOrganizationResponse::class);
    }

    public function findMyOwnOrganizations(FindMyOwnOrganizationsRequest $request): ?FindMyOwnOrganizationsResponse
    {
        $useCase = new FindMyOwnOrganizationsImpl($this->organizations);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute($request, $presenter, $this->signedIn()), FindMyOwnOrganizationsResponse::class);
    }

    public function updateOrganization(UpdateOrganizationRequest $request): ?UpdateOrganizationResponse
    {
        $useCase = new UpdateOrganizationImpl($this->organizations, $this->storage, $this->notifications, $this->geoLocation);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute($request, $presenter), UpdateOrganizationResponse::class);
    }

    public function removeOrganizationLogo(RemoveOrganizationLogoRequest $request): ?RemoveOrganizationLogoResponse
    {
        $useCase = new RemoveOrganizationLogoImpl($this->organizations, $this->storage, $this->notifications);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute($request, $presenter, $this->signedIn()), RemoveOrganizationLogoResponse::class);
    }

    public function getMyAccountInformation(): ?GetMyAccountInformationResponse
    {
        $useCase = new GetMyAccountInformationImpl($this->users, $this->organizations);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute(new GetMyAccountInformationRequest(), $presenter, $this->signedIn()), GetMyAccountInformationResponse::class);
    }

    public function updateMyAccountInformation(UpdateMyAccountInformationRequest $request): ?UpdateMyAccountInformationResponse
    {
        $useCase = new UpdateMyAccountInformationImpl($this->organizations, $this->users, $this->transaction, $this->storage, $this->notifications);

        return $this->attempt(fn (LastResponsePresenter $presenter) => $useCase->execute($request, $presenter, $this->signedIn()), UpdateMyAccountInformationResponse::class);
    }

    /**
     * The business refusal of the last use case, null when it succeeded.
     */
    public function refusal(): ?UseCaseException
    {
        return $this->refusal;
    }

    /**
     * @template T of object
     *
     * @param callable(LastResponsePresenter): mixed $execute
     * @param class-string<T>                       $responseClass
     *
     * @return T|null
     */
    private function attempt(callable $execute, string $responseClass): ?object
    {
        $this->refusal = null;
        $presenter = new LastResponsePresenter();

        try {
            $execute($presenter);
        } catch (UseCaseException $refusal) {
            $this->refusal = $refusal;

            return null;
        }

        return $presenter->response($responseClass);
    }
}
