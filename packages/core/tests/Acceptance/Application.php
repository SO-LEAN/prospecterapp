<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo\ChangeMyOrganizationLogoHandler;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentityHandler;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetailsHandler;
use Prospecter\Core\PersonalData\Application\ChangePersonPicture\ChangePersonPictureHandler;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonHandler;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersonsHandler;
use Prospecter\Core\PersonalData\Application\PersonResponse;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePersonHandler;
use Prospecter\Core\Prospecting\Application\AttachToHolding\AttachToHoldingHandler;
use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetailsHandler;
use Prospecter\Core\Prospecting\Application\ChangeLogo\ChangeLogoHandler;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganizationHandler;
use Prospecter\Core\Prospecting\Application\DetachFromHolding\DetachFromHoldingHandler;
use Prospecter\Core\Prospecting\Application\OrganizationResponse;
use Prospecter\Core\Prospecting\Application\Relocate\RelocateHandler;
use Prospecter\Core\Prospecting\Application\RemoveLogo\RemoveLogoHandler;
use Prospecter\Core\Prospecting\Application\ReviseLegalIdentity\ReviseLegalIdentityHandler;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsHandler;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsResponse;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspectingHandler;
use Prospecter\Core\Prospecting\Application\WriteObservations\WriteObservationsHandler;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Double\InMemoryAccountRepository;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryMemberRepository;
use Tests\Core\Double\InMemoryOrganizationRepository;
use Tests\Core\Double\InMemoryPersonRepository;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\KnownPlacesGeoLocation;
use Tests\Core\Double\MembersAsProspectors;
use Tests\Core\Double\MembersAsReaders;
use Tests\Core\Double\TimeOrderedUuids;

/**
 * The hexagon as the scenarios use it: the real handlers, wired to in-memory implementations of their
 * ports, called the way a delivery calls them: through a RefusalTranslatingHandler, a command is sent to
 * its handler (a command bus in the API) and returns nothing, a query returns what it reads.
 */
final class Application
{
    private const string FIRST_DAY = '2026-10-15 09:00';

    public readonly InMemoryOrganizationRepository $organizations;
    public readonly InMemoryAccountRepository $accounts;
    public readonly InMemoryMemberRepository $members;
    public readonly InMemoryPersonRepository $persons;
    public readonly InMemoryStorage $storage;
    public readonly KnownPlacesGeoLocation $geoLocation;

    public readonly MembersAsProspectors $prospectors;
    public readonly MembersAsReaders $readers;
    /** @var array<class-string, CommandHandler> */
    private array $handlers = [];
    private ?string $signedIn = null;
    private ?UseCaseRefused $refusal = null;
    private \DateTimeImmutable $now;
    private readonly TimeOrderedUuids $uuids;

    public function __construct()
    {
        $database = new InMemoryDatabase();
        $this->now = new \DateTimeImmutable(self::FIRST_DAY);
        $this->uuids = new TimeOrderedUuids();
        $this->organizations = new InMemoryOrganizationRepository($database);
        $this->accounts = new InMemoryAccountRepository($database);
        $this->members = new InMemoryMemberRepository($database);
        $this->prospectors = new MembersAsProspectors($this->members);
        $this->persons = new InMemoryPersonRepository($database);
        $this->readers = new MembersAsReaders($this->members);
        $this->storage = new InMemoryStorage();
        $this->geoLocation = new KnownPlacesGeoLocation();

        foreach ($this->commandHandlers() as $handler) {
            $this->handlers[$this->commandOf($handler)] = $handler;
        }
    }

    public function signIn(MemberId $user): void
    {
        $this->signedIn = $user->value;
    }

    public function signedIn(): string
    {
        return $this->signedIn ?? throw new \LogicException('Nobody is signed in: add "je suis connecté..." to the scenario');
    }

    /**
     * The time the client gives the commands it sends.
     */
    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function itIs(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    /**
     * An identity for what the client is about to create (UUID v7, as the API generates them).
     */
    public function newIdentity(): string
    {
        return $this->uuids->next();
    }

    public function dispatch(object $command): void
    {
        $handler = $this->handlers[$command::class] ?? throw new \LogicException(sprintf('No handler for %s', $command::class));
        $this->attempt(static fn (): mixed => new RefusalTranslatingHandler($handler)($command));
    }

    public function consultOrganization(ConsultOrganization $query): ?OrganizationResponse
    {
        return $this->attempt(fn (): OrganizationResponse => new RefusalTranslatingHandler(new ConsultOrganizationHandler($this->organizations, $this->prospectors))($query));
    }

    public function searchProspectedOrganizations(SearchProspectedOrganizations $query): ?SearchProspectedOrganizationsResponse
    {
        return $this->attempt(fn (): SearchProspectedOrganizationsResponse => new RefusalTranslatingHandler(new SearchProspectedOrganizationsHandler($this->organizations, $this->prospectors))($query));
    }

    public function consultMyAccount(): ?AccountResponse
    {
        return $this->attempt(fn (): AccountResponse => new RefusalTranslatingHandler(new ConsultMyAccountHandler($this->members, $this->accounts))(new ConsultMyAccount($this->signedIn())));
    }

    public function consultPerson(ConsultPerson $query): ?PersonResponse
    {
        return $this->attempt(fn (): PersonResponse => new RefusalTranslatingHandler(new ConsultPersonHandler($this->persons, $this->readers))($query));
    }

    /**
     * @return array<string, PersonResponse>|null
     */
    public function consultPersons(ConsultPersons $query): ?array
    {
        return $this->attempt(fn (): array => new RefusalTranslatingHandler(new ConsultPersonsHandler($this->persons, $this->readers))($query));
    }

    /**
     * The business refusal of the last command or query, null when it succeeded.
     */
    public function refusal(): ?UseCaseRefused
    {
        return $this->refusal;
    }

    /**
     * @return list<CommandHandler>
     */
    private function commandHandlers(): array
    {
        return [
            new StartProspectingHandler($this->organizations, $this->prospectors, $this->geoLocation),
            new ReviseLegalIdentityHandler($this->organizations, $this->prospectors),
            new ChangeContactDetailsHandler($this->organizations, $this->prospectors),
            new RelocateHandler($this->organizations, $this->prospectors, $this->geoLocation),
            new WriteObservationsHandler($this->organizations, $this->prospectors),
            new AttachToHoldingHandler($this->organizations, $this->prospectors),
            new DetachFromHoldingHandler($this->organizations, $this->prospectors),
            new ChangeLogoHandler($this->organizations, $this->prospectors, $this->storage),
            new RemoveLogoHandler($this->organizations, $this->prospectors, $this->storage),
            new RenamePersonHandler($this->persons, $this->readers),
            new ChangePersonContactDetailsHandler($this->persons, $this->readers),
            new ChangePersonPictureHandler($this->persons, $this->readers, $this->storage),
            new ReviseMyOrganizationIdentityHandler($this->accounts, $this->members),
            new ChangeMyOrganizationLogoHandler($this->accounts, $this->members, $this->storage),
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
        } catch (UseCaseRefused $refusal) {
            $this->refusal = $refusal;

            return null;
        }
    }
}
