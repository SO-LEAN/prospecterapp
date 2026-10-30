<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo\ChangeMyOrganizationLogoHandler;
use Prospecter\Core\Accounts\Application\ConsultMember\ConsultMemberHandler;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\KeptAccount;
use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Application\OpenAccount\OpenAccountHandler;
use Prospecter\Core\Accounts\Application\PublishAccountOpened\PublishAccountOpenedHandler;
use Prospecter\Core\Accounts\Application\PublishMemberRegistered\PublishMemberRegisteredHandler;
use Prospecter\Core\Accounts\Application\RegisterAMember\RegisterAMemberHandler;
use Prospecter\Core\Accounts\Application\RegisterTheFounder\RegisterTheFounderHandler;
use Prospecter\Core\Accounts\Application\RelocateMyOrganization\RelocateMyOrganizationHandler;
use Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentityHandler;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\ConsultMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\KeptMember;
use Prospecter\Core\Accounts\Contract\Service\ConsultMember\MemberResponse;
use Prospecter\Core\PersonalData\Application\ChangeJobTitle\ChangeJobTitleHandler;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetailsHandler;
use Prospecter\Core\PersonalData\Application\ChangePersonPicture\ChangePersonPictureHandler;
use Prospecter\Core\PersonalData\Application\CollectContact\CollectContactHandler;
use Prospecter\Core\PersonalData\Application\CollectTheFounderProfile\CollectTheFounderProfileHandler;
use Prospecter\Core\PersonalData\Application\CollectTheMemberProfile\CollectTheMemberProfileHandler;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPerson;
use Prospecter\Core\PersonalData\Application\ConsultPerson\ConsultPersonHandler;
use Prospecter\Core\PersonalData\Application\ConsultPerson\KeptPerson;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersonsHandler;
use Prospecter\Core\PersonalData\Application\ConsultPersons\KeptPersons;
use Prospecter\Core\PersonalData\Application\ForgetContact\ForgetContactHandler;
use Prospecter\Core\PersonalData\Application\ForgetContactsLeftWithoutOrganization\ForgetContactsLeftWithoutOrganizationHandler;
use Prospecter\Core\PersonalData\Application\IsAContactOf\IsAContactOfHandler;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\PublishContactCollected\PublishContactCollectedHandler;
use Prospecter\Core\PersonalData\Application\PublishContactForgotten\PublishContactForgottenHandler;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\KeptCallingContacts;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContactHandler;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePersonHandler;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\KeptContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContactsHandler;
use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\IsAContactOf;
use Prospecter\Core\PersonalData\Contract\Service\IsAContactOf\KeptIsAContact;
use Prospecter\Core\PersonalData\Domain\Service\EmailUniqueness as PersonalDataEmailUniqueness;
use Prospecter\Core\Prospecting\Application\AddContact\AddContactHandler;
use Prospecter\Core\Prospecting\Application\AttachCollectedContact\AttachCollectedContactHandler;
use Prospecter\Core\Prospecting\Application\AttachedFiles;
use Prospecter\Core\Prospecting\Application\AttachFile\AttachFileHandler;
use Prospecter\Core\Prospecting\Application\AttachToHolding\AttachToHoldingHandler;
use Prospecter\Core\Prospecting\Application\ChangeContactDetails\ChangeContactDetailsHandler;
use Prospecter\Core\Prospecting\Application\ChangeLogo\ChangeLogoHandler;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchangeHandler;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\ConsultExchange\KeptExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform\ConsultExchangesToMakeConform;
use Prospecter\Core\Prospecting\Application\ConsultExchangesToMakeConform\ConsultExchangesToMakeConformHandler;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\ConsultOrganizationHandler;
use Prospecter\Core\Prospecting\Application\ConsultOrganization\KeptOrganization;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimeline;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationTimeline\ConsultOrganizationTimelineHandler;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimelineHandler;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntentsHandler;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\KeptTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\TimelineIntentsResponse;
use Prospecter\Core\Prospecting\Application\CorrectExchange\CorrectExchangeHandler;
use Prospecter\Core\Prospecting\Application\CorrectExchangeText\CorrectExchangeTextHandler;
use Prospecter\Core\Prospecting\Application\DetachForgottenContact\DetachForgottenContactHandler;
use Prospecter\Core\Prospecting\Application\DetachFromHolding\DetachFromHoldingHandler;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\AttachmentDownloadResponse;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachment;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachmentHandler;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\KeptDownload;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchangeHandler;
use Prospecter\Core\Prospecting\Application\IsProspectedBy\IsProspectedByHandler;
use Prospecter\Core\Prospecting\Application\KeepExchangesOfForgottenContact\KeepExchangesOfForgottenContactHandler;
use Prospecter\Core\Prospecting\Application\KeptTimeline;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchangeHandler;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchangeHandler;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PublishContactsLeftWithoutOrganization\PublishContactsLeftWithoutOrganizationHandler;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\KeptCallingOrganizations;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\RecognizeCallingOrganization;
use Prospecter\Core\Prospecting\Application\RecognizeCallingOrganization\RecognizeCallingOrganizationHandler;
use Prospecter\Core\Prospecting\Application\Relocate\RelocateHandler;
use Prospecter\Core\Prospecting\Application\RemoveAttachment\RemoveAttachmentHandler;
use Prospecter\Core\Prospecting\Application\RemoveContact\RemoveContactHandler;
use Prospecter\Core\Prospecting\Application\RemoveLogo\RemoveLogoHandler;
use Prospecter\Core\Prospecting\Application\ReviseLegalIdentity\ReviseLegalIdentityHandler;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\FoundOrganizationPage;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\KeptOrganizationPage;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizations;
use Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations\SearchProspectedOrganizationsHandler;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspectingHandler;
use Prospecter\Core\Prospecting\Application\StopKeepingExchanges\StopKeepingExchangesHandler;
use Prospecter\Core\Prospecting\Application\StopProspecting\StopProspectingHandler;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Application\WriteObservations\WriteObservationsHandler;
use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\IsProspectedBy;
use Prospecter\Core\Prospecting\Contract\Service\IsProspectedBy\KeptIsProspected;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Service\EmailUniqueness as ProspectingEmailUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\GeoLocalizer;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Double\AccountsProspectors;
use Tests\Core\Double\AccountsReaders;
use Tests\Core\Double\InMemoryAccountRepository;
use Tests\Core\Double\InMemoryContactPageRetriever;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryExchangeDetailRetriever;
use Tests\Core\Double\InMemoryExchangeRepository;
use Tests\Core\Double\InMemoryFoundOrganizationRetriever;
use Tests\Core\Double\InMemoryMemberRepository;
use Tests\Core\Double\InMemoryMemberRetriever;
use Tests\Core\Double\InMemoryMyAccountRetriever;
use Tests\Core\Double\InMemoryOrganizationRepository;
use Tests\Core\Double\InMemoryOrganizationSheetRetriever;
use Tests\Core\Double\InMemoryPersonCardRetriever;
use Tests\Core\Double\InMemoryPersonRepository;
use Tests\Core\Double\InMemoryPrivateStorage;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\InMemoryTimelineRetriever;
use Tests\Core\Double\KnownPlacesGeoLocation;
use Tests\Core\Double\PersonalDataKnownContacts;
use Tests\Core\Double\ProspectingOrganizations;
use Tests\Core\Double\SynchronousEvents;
use Tests\Core\Double\TimeOrderedUuids;

/**
 * The hexagon as the scenarios use it: the real handlers, wired to in-memory implementations of their
 * ports, called the way a delivery calls them: through a RefusalTranslatingHandler, a command is sent to
 * its handler (a command bus in the API) and returns nothing, a query presents what it reads to the presenter
 * of the core keeping it as it is (ADR 0085).
 */
final class Application
{
    private const string FIRST_DAY = '2026-10-15 09:00';

    public readonly InMemoryOrganizationRepository $organizations;
    public readonly InMemoryFoundOrganizationRetriever $foundOrganizationRetriever;
    private readonly InMemoryOrganizationSheetRetriever $organizationSheetRetriever;
    private readonly InMemoryExchangeDetailRetriever $exchangeDetailRetriever;
    private readonly InMemoryTimelineRetriever $timelineRetriever;
    private readonly InMemoryMyAccountRetriever $myAccountRetriever;
    private readonly InMemoryMemberRetriever $memberRetriever;
    private readonly InMemoryPersonCardRetriever $personCardRetriever;
    private readonly InMemoryContactPageRetriever $contactPageRetriever;
    public readonly InMemoryExchangeRepository $exchanges;
    public readonly InMemoryAccountRepository $accounts;
    public readonly InMemoryMemberRepository $members;
    public readonly InMemoryPersonRepository $persons;
    public readonly InMemoryStorage $storage;
    public readonly InMemoryPrivateStorage $privateStorage;
    public readonly KnownPlacesGeoLocation $geoLocation;

    public readonly AccountsProspectors $prospectors;
    public readonly AccountsReaders $readers;
    public readonly PersonalDataKnownContacts $knownContacts;
    public readonly ProspectingOrganizations $prospectedOrganizations;
    public readonly SynchronousEvents $events;
    private readonly FileStore $files;
    private readonly GeoLocalizer $geoLocalizer;
    private readonly PersonalDataEmailUniqueness $personEmails;
    private readonly ProspectingEmailUniqueness $organizationEmails;
    private readonly OrphanContacts $orphans;
    private readonly ContactOrganizations $contactOrganizations;
    private readonly AttachedFiles $attachedFiles;
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
        $this->foundOrganizationRetriever = new InMemoryFoundOrganizationRetriever($database);
        $this->organizationSheetRetriever = new InMemoryOrganizationSheetRetriever($database);
        $this->exchangeDetailRetriever = new InMemoryExchangeDetailRetriever($database);
        $this->timelineRetriever = new InMemoryTimelineRetriever($database);
        $this->exchanges = new InMemoryExchangeRepository($database);
        $this->accounts = new InMemoryAccountRepository($database);
        $this->members = new InMemoryMemberRepository($database);
        $this->myAccountRetriever = new InMemoryMyAccountRetriever($database);
        $this->memberRetriever = new InMemoryMemberRetriever($database);
        $this->persons = new InMemoryPersonRepository($database);
        $this->personCardRetriever = new InMemoryPersonCardRetriever($database);
        $this->contactPageRetriever = new InMemoryContactPageRetriever($database);
        $this->prospectors = new AccountsProspectors($this->published(new ConsultMemberHandler($this->memberRetriever)));
        $this->readers = new AccountsReaders($this->published(new ConsultMemberHandler($this->memberRetriever)));
        $this->knownContacts = new PersonalDataKnownContacts($this->published(new IsAContactOfHandler($this->persons)));
        $this->prospectedOrganizations = new ProspectingOrganizations($this->published(new IsProspectedByHandler($this->organizations)));
        $this->events = new SynchronousEvents();
        $this->storage = new InMemoryStorage();
        $this->privateStorage = new InMemoryPrivateStorage();
        $this->attachedFiles = new AttachedFiles($this->privateStorage);
        $this->geoLocation = new KnownPlacesGeoLocation();
        $this->files = new FileStore($this->storage);
        $this->geoLocalizer = new GeoLocalizer($this->geoLocation);
        $this->personEmails = new PersonalDataEmailUniqueness($this->persons);
        $this->organizationEmails = new ProspectingEmailUniqueness($this->organizations);
        $this->orphans = new OrphanContacts($this->organizations);
        $this->contactOrganizations = new ContactOrganizations($this->organizations);

        foreach ($this->eventHandlers() as $handler) {
            $this->events->subscribe($handler);
        }

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
        $this->attempt(static function () use ($handler, $command): void {
            new RefusalTranslatingHandler($handler)($command);
        });
    }

    public function consultOrganization(ConsultOrganization $query): ?OrganizationSheet
    {
        $kept = new KeptOrganization();

        return $this->ask(new ConsultOrganizationHandler($this->organizationSheetRetriever), $query, $kept) ? $kept->organization : null;
    }

    public function searchProspectedOrganizations(SearchProspectedOrganizations $query): ?FoundOrganizationPage
    {
        $kept = new KeptOrganizationPage();

        return $this->ask(new SearchProspectedOrganizationsHandler($this->foundOrganizationRetriever, $this->prospectors), $query, $kept) ? $kept->page : null;
    }

    public function consultMyAccount(): ?MyAccount
    {
        $kept = new KeptAccount();

        return $this->ask(new ConsultMyAccountHandler($this->myAccountRetriever), new ConsultMyAccount($this->signedIn()), $kept) ? $kept->account : null;
    }

    public function consultPerson(ConsultPerson $query): ?PersonCard
    {
        $kept = new KeptPerson();

        return $this->ask(new ConsultPersonHandler($this->personCardRetriever, $this->readers), $query, $kept) ? $kept->person : null;
    }

    /**
     * @return array<string, PersonCard>|null
     */
    public function consultPersons(ConsultPersons $query): ?array
    {
        $kept = new KeptPersons();

        return $this->ask(new ConsultPersonsHandler($this->personCardRetriever, $this->readers), $query, $kept) ? $kept->persons : null;
    }

    public function searchContacts(SearchContacts $query): ?ContactPage
    {
        $kept = new KeptContactPage();

        return $this->ask(new SearchContactsHandler($this->contactPageRetriever, $this->readers), $query, $kept) ? $kept->page : null;
    }

    /**
     * @return list<PersonCard>|null
     */
    public function recognizeACallingContact(RecognizeCallingContact $query): ?array
    {
        $kept = new KeptCallingContacts();

        return $this->ask(new RecognizeCallingContactHandler($this->personCardRetriever, $this->readers), $query, $kept) ? $kept->contacts : null;
    }

    /**
     * @return list<OrganizationSheet>|null
     */
    public function recognizeACallingOrganization(RecognizeCallingOrganization $query): ?array
    {
        $kept = new KeptCallingOrganizations();

        return $this->ask(new RecognizeCallingOrganizationHandler($this->organizationSheetRetriever, $this->prospectors), $query, $kept) ? $kept->organizations : null;
    }

    public function consultTimeline(ConsultTimeline $query): ?Timeline
    {
        $kept = new KeptTimeline();

        return $this->ask(new ConsultTimelineHandler($this->timelineRetriever, $this->prospectors, $this->contactOrganizations), $query, $kept) ? $kept->timeline : null;
    }

    public function consultOrganizationTimeline(ConsultOrganizationTimeline $query): ?Timeline
    {
        $kept = new KeptTimeline();

        return $this->ask(new ConsultOrganizationTimelineHandler($this->timelineRetriever, $this->organizationSheetRetriever, $this->prospectors), $query, $kept) ? $kept->timeline : null;
    }

    public function consultExchangesToMakeConform(ConsultExchangesToMakeConform $query): ?Timeline
    {
        $kept = new KeptTimeline();

        return $this->ask(new ConsultExchangesToMakeConformHandler($this->timelineRetriever, $this->organizationSheetRetriever, $this->prospectors), $query, $kept) ? $kept->timeline : null;
    }

    public function consultTimelineIntents(ConsultTimelineIntents $query): ?TimelineIntentsResponse
    {
        $kept = new KeptTimelineIntents();

        return $this->ask(new ConsultTimelineIntentsHandler($this->prospectors, $this->contactOrganizations), $query, $kept) ? $kept->intents : null;
    }

    public function consultExchange(ConsultExchange $query): ?ExchangeDetail
    {
        $kept = new KeptExchange();

        return $this->ask(new ConsultExchangeHandler($this->exchangeDetailRetriever, $this->prospectors), $query, $kept) ? $kept->exchange : null;
    }

    public function downloadAttachment(DownloadAttachment $query): ?AttachmentDownloadResponse
    {
        $kept = new KeptDownload();

        return $this->ask(new DownloadAttachmentHandler($this->exchanges, $this->prospectors, $this->privateStorage), $query, $kept) ? $kept->download : null;
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
            new StartProspectingHandler($this->organizations, $this->prospectors, $this->events, $this->geoLocalizer, $this->organizationEmails),
            new ReviseLegalIdentityHandler($this->organizations, $this->events),
            new ChangeContactDetailsHandler($this->organizations, $this->events, $this->organizationEmails),
            new RelocateHandler($this->organizations, $this->events, $this->geoLocalizer),
            new WriteObservationsHandler($this->organizations, $this->events),
            new AttachToHoldingHandler($this->organizations, $this->events),
            new DetachFromHoldingHandler($this->organizations, $this->events),
            new ChangeLogoHandler($this->organizations, $this->events, $this->files),
            new RemoveLogoHandler($this->organizations, $this->events, $this->files),
            new RenamePersonHandler($this->persons, $this->readers, $this->events),
            new ChangePersonContactDetailsHandler($this->persons, $this->readers, $this->events, $this->personEmails),
            new ChangePersonPictureHandler($this->persons, $this->readers, $this->events, $this->files),
            new ReviseMyOrganizationIdentityHandler($this->accounts, $this->members, $this->events),
            new RelocateMyOrganizationHandler($this->accounts, $this->members, $this->events, $this->geoLocalizer),
            new ChangeMyOrganizationLogoHandler($this->accounts, $this->members, $this->events, $this->files),
            new OpenAccountHandler($this->accounts, $this->events),
            new RegisterAMemberHandler($this->members, $this->events),
            new CollectContactHandler($this->persons, $this->readers, $this->prospectedOrganizations, $this->events, $this->personEmails),
            new ChangeJobTitleHandler($this->persons, $this->readers, $this->events),
            new ForgetContactHandler($this->persons, $this->readers, $this->events),
            new AddContactHandler($this->organizations, $this->knownContacts, $this->events),
            new RemoveContactHandler($this->organizations, $this->events, $this->orphans),
            new StopProspectingHandler($this->organizations, $this->events, $this->orphans),
            new NoteExchangeHandler($this->exchanges, $this->prospectors, $this->contactOrganizations, $this->events),
            new NoteOrganizationExchangeHandler($this->exchanges, $this->organizations, $this->contactOrganizations, $this->events),
            new CorrectExchangeHandler($this->exchanges, $this->events),
            new CorrectExchangeTextHandler($this->exchanges, $this->events),
            new EraseExchangeHandler($this->exchanges, $this->events, $this->attachedFiles),
            new AttachFileHandler($this->exchanges, $this->privateStorage, $this->events),
            new RemoveAttachmentHandler($this->exchanges, $this->privateStorage, $this->events),
        ];
    }

    /**
     * The queries a context publishes to the others (ADR 0045), asked as their anticorruption layers ask them.
     */
    public function consultMember(ConsultMember $query): ?MemberResponse
    {
        $kept = new KeptMember();

        return $this->ask(new ConsultMemberHandler($this->memberRetriever), $query, $kept) ? $kept->member : null;
    }

    public function isAContactOf(IsAContactOf $query): ?bool
    {
        $kept = new KeptIsAContact();

        return $this->ask(new IsAContactOfHandler($this->persons), $query, $kept) ? $kept->isAContact : null;
    }

    public function isProspectedBy(IsProspectedBy $query): ?bool
    {
        $kept = new KeptIsProspected();

        return $this->ask(new IsProspectedByHandler($this->organizations), $query, $kept) ? $kept->isProspected : null;
    }

    private function published(QueryHandler $handler): RefusalTranslatingHandler
    {
        return new RefusalTranslatingHandler($handler);
    }

    /**
     * Every event handler of the core: the reactions of the other contexts run in the scenarios as in the API
     * (`EventHandlersSubscribedTest` fails when one is missing).
     *
     * @return list<EventHandler>
     */
    private function eventHandlers(): array
    {
        return [
            new AttachCollectedContactHandler($this->organizations, $this->events),
            new DetachForgottenContactHandler($this->organizations, $this->events, $this->orphans),
            new KeepExchangesOfForgottenContactHandler($this->exchanges, $this->events),
            new ForgetContactsLeftWithoutOrganizationHandler($this->persons, $this->readers, $this->events),
            new RegisterTheFounderHandler($this->members),
            new CollectTheFounderProfileHandler($this->persons),
            new PublishAccountOpenedHandler($this->events),
            new CollectTheMemberProfileHandler($this->persons),
            new PublishMemberRegisteredHandler($this->events),
            new PublishContactCollectedHandler($this->events),
            new PublishContactForgottenHandler($this->events),
            new PublishContactsLeftWithoutOrganizationHandler($this->events),
            new StopKeepingExchangesHandler($this->exchanges, $this->events, $this->attachedFiles),
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
     * Asks a query, its answer presented to the output port given; false when it was refused.
     */
    private function ask(QueryHandler $handler, object $query, object $output): bool
    {
        return null !== $this->attempt(static function () use ($handler, $query, $output): bool {
            new RefusalTranslatingHandler($handler)($query, $output);

            return true;
        });
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
