<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Application\Account\AccountResponse;
use Prospecter\Core\Application\Account\ChangeMyContactDetails\ChangeMyContactDetailsHandler;
use Prospecter\Core\Application\Account\ChangeMyName\ChangeMyNameHandler;
use Prospecter\Core\Application\Account\ChangeMyOrganizationLogo\ChangeMyOrganizationLogoHandler;
use Prospecter\Core\Application\Account\ChangeMyPicture\ChangeMyPictureHandler;
use Prospecter\Core\Application\Account\ChangeMyUserName\ChangeMyUserNameHandler;
use Prospecter\Core\Application\Account\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Application\Account\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\Application\Account\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentityHandler;
use Prospecter\Core\Application\CommandHandler;
use Prospecter\Core\Application\Organization\AttachToHolding\AttachToHoldingHandler;
use Prospecter\Core\Application\Organization\ChangeContactDetails\ChangeContactDetailsHandler;
use Prospecter\Core\Application\Organization\ChangeLogo\ChangeLogoHandler;
use Prospecter\Core\Application\Organization\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Application\Organization\ConsultOrganization\ConsultOrganizationHandler;
use Prospecter\Core\Application\Organization\DetachFromHolding\DetachFromHoldingHandler;
use Prospecter\Core\Application\Organization\OrganizationResponse;
use Prospecter\Core\Application\Organization\Relocate\RelocateHandler;
use Prospecter\Core\Application\Organization\RemoveLogo\RemoveLogoHandler;
use Prospecter\Core\Application\Organization\ReviseLegalIdentity\ReviseLegalIdentityHandler;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsHandler;
use Prospecter\Core\Application\Organization\SearchProspectedOrganizations\SearchProspectedOrganizationsResponse;
use Prospecter\Core\Application\Organization\StartProspecting\StartProspectingHandler;
use Prospecter\Core\Application\Organization\WriteObservations\WriteObservationsHandler;
use Prospecter\Core\Domain\Shared\BusinessRuleViolation;
use Prospecter\Core\Domain\Shared\NotFound;
use Prospecter\Core\Domain\User\UserId;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryOrganizationRepository;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\InMemoryUserRepository;
use Tests\Core\Double\KnownPlacesGeoLocation;

/**
 * The hexagon as the scenarios use it: the real handlers, wired to in-memory implementations of their
 * ports, called the way a delivery calls them: a command is sent to its handler (a command bus in the
 * API) and returns nothing, a query returns what it reads.
 */
final class Application
{
    public readonly InMemoryOrganizationRepository $organizations;
    public readonly InMemoryUserRepository $users;
    public readonly InMemoryStorage $storage;
    public readonly KnownPlacesGeoLocation $geoLocation;

    /** @var array<class-string, CommandHandler> */
    private array $handlers = [];
    private ?UserId $signedIn = null;
    private NotFound|BusinessRuleViolation|null $refusal = null;

    public function __construct()
    {
        $database = new InMemoryDatabase();
        $this->organizations = new InMemoryOrganizationRepository($database);
        $this->users = new InMemoryUserRepository($database);
        $this->storage = new InMemoryStorage();
        $this->geoLocation = new KnownPlacesGeoLocation();

        foreach ($this->commandHandlers() as $handler) {
            $this->handlers[$this->commandOf($handler)] = $handler;
        }
    }

    public function signIn(UserId $user): void
    {
        $this->signedIn = $user;
    }

    public function signedIn(): UserId
    {
        return $this->signedIn ?? throw new \LogicException('Nobody is signed in: add "je suis connecté..." to the scenario');
    }

    public function dispatch(object $command): void
    {
        $handler = $this->handlers[$command::class] ?? throw new \LogicException(sprintf('No handler for %s', $command::class));
        $this->attempt(static fn () => $handler($command));
    }

    public function consultOrganization(ConsultOrganization $query): ?OrganizationResponse
    {
        return $this->attempt(fn (): OrganizationResponse => new ConsultOrganizationHandler($this->organizations, $this->users)($query));
    }

    public function searchProspectedOrganizations(SearchProspectedOrganizations $query): ?SearchProspectedOrganizationsResponse
    {
        return $this->attempt(fn (): SearchProspectedOrganizationsResponse => new SearchProspectedOrganizationsHandler($this->organizations, $this->users)($query));
    }

    public function consultMyAccount(): ?AccountResponse
    {
        return $this->attempt(fn (): AccountResponse => new ConsultMyAccountHandler($this->users, $this->organizations)(new ConsultMyAccount($this->signedIn())));
    }

    /**
     * The business refusal of the last command or query, null when it succeeded.
     */
    public function refusal(): NotFound|BusinessRuleViolation|null
    {
        return $this->refusal;
    }

    /**
     * @return list<CommandHandler>
     */
    private function commandHandlers(): array
    {
        return [
            new StartProspectingHandler($this->organizations, $this->users, $this->geoLocation),
            new ReviseLegalIdentityHandler($this->organizations, $this->users),
            new ChangeContactDetailsHandler($this->organizations, $this->users),
            new RelocateHandler($this->organizations, $this->users, $this->geoLocation),
            new WriteObservationsHandler($this->organizations, $this->users),
            new AttachToHoldingHandler($this->organizations, $this->users),
            new DetachFromHoldingHandler($this->organizations, $this->users),
            new ChangeLogoHandler($this->organizations, $this->users, $this->storage),
            new RemoveLogoHandler($this->organizations, $this->users, $this->storage),
            new ChangeMyNameHandler($this->users),
            new ChangeMyUserNameHandler($this->users),
            new ChangeMyContactDetailsHandler($this->users),
            new ChangeMyPictureHandler($this->users, $this->storage),
            new ReviseMyOrganizationIdentityHandler($this->organizations, $this->users),
            new ChangeMyOrganizationLogoHandler($this->organizations, $this->users, $this->storage),
        ];
    }

    /**
     * The command a handler carries out, found the way the command bus of the API finds it: the type of
     * the argument of __invoke().
     *
     * @return class-string
     */
    private function commandOf(CommandHandler $handler): string
    {
        $type = new \ReflectionMethod($handler, '__invoke')->getParameters()[0]->getType();

        return $type instanceof \ReflectionNamedType ? $type->getName() : throw new \LogicException(sprintf('%s::__invoke() must take one command', $handler::class));
    }

    /**
     * @template T
     *
     * @param \Closure(): T $call
     *
     * @return T|null
     */
    private function attempt(\Closure $call): mixed
    {
        $this->refusal = null;

        try {
            return $call();
        } catch (NotFound|BusinessRuleViolation $refusal) {
            $this->refusal = $refusal;

            return null;
        }
    }
}
