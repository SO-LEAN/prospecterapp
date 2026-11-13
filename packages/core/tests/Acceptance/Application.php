<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance;

use Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo\ChangeMyOrganizationLogoHandler;
use Prospecter\Core\Accounts\Application\ConsultMembers\ConsultMembersHandler;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccount;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\ConsultMyAccountHandler;
use Prospecter\Core\Accounts\Application\ConsultMyAccount\KeptAccount;
use Prospecter\Core\Accounts\Application\ListAccounts\ListAccountsHandler;
use Prospecter\Core\Accounts\Application\MyAccount;
use Prospecter\Core\Accounts\Application\OpenAccount\OpenAccountHandler;
use Prospecter\Core\Accounts\Application\PublishAccountOpened\PublishAccountOpenedHandler;
use Prospecter\Core\Accounts\Application\PublishMemberRegistered\PublishMemberRegisteredHandler;
use Prospecter\Core\Accounts\Application\RegisterAMember\RegisterAMemberHandler;
use Prospecter\Core\Accounts\Application\RegisterTheFounder\RegisterTheFounderHandler;
use Prospecter\Core\Accounts\Application\RelocateMyOrganization\RelocateMyOrganizationHandler;
use Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity\ReviseMyOrganizationIdentityHandler;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\ConsultMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\KeptMembers;
use Prospecter\Core\Accounts\Contract\Service\ConsultMembers\MemberResponse;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\KeptAccounts;
use Prospecter\Core\Accounts\Contract\Service\ListAccounts\ListAccounts;
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
use Prospecter\Core\PersonalData\Application\ListContactFacts\ListContactFactsHandler;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\PersonalData\Application\PublishContactCollected\PublishContactCollectedHandler;
use Prospecter\Core\PersonalData\Application\PublishContactFacts\PublishContactFactsHandler;
use Prospecter\Core\PersonalData\Application\PublishContactForgotten\PublishContactForgottenHandler;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\KeptCallingContacts;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContact;
use Prospecter\Core\PersonalData\Application\RecognizeCallingContact\RecognizeCallingContactHandler;
use Prospecter\Core\PersonalData\Application\RenamePerson\RenamePersonHandler;
use Prospecter\Core\PersonalData\Application\SearchContacts\ContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\KeptContactPage;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContacts;
use Prospecter\Core\PersonalData\Application\SearchContacts\SearchContactsHandler;
use Prospecter\Core\PersonalData\Application\WhichAreContacts\WhichAreContactsHandler;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\KeptContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\ListContactFacts\ListContactFacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\KeptContacts;
use Prospecter\Core\PersonalData\Contract\Service\WhichAreContacts\WhichAreContacts;
use Prospecter\Core\PersonalData\Domain\Service\EmailUniqueness as PersonalDataEmailUniqueness;
use Prospecter\Core\Prospecting\Application\AddContact\AddContactHandler;
use Prospecter\Core\Prospecting\Application\AttachCollectedContact\AttachCollectedContactHandler;
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
use Prospecter\Core\Prospecting\Application\ConsultOrganizationUpcomingExchanges\ConsultOrganizationUpcomingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultOrganizationUpcomingExchanges\ConsultOrganizationUpcomingExchangesHandler;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\ConsultOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\ConsultOverlappingExchangesHandler;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\KeptOverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges\OverlappingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimelineHandler;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\ConsultTimelineIntentsHandler;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\KeptTimelineIntents;
use Prospecter\Core\Prospecting\Application\ConsultTimelineIntents\TimelineIntentsResponse;
use Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges\ConsultUpcomingExchanges;
use Prospecter\Core\Prospecting\Application\ConsultUpcomingExchanges\ConsultUpcomingExchangesHandler;
use Prospecter\Core\Prospecting\Application\CorrectExchange\CorrectExchangeHandler;
use Prospecter\Core\Prospecting\Application\CorrectExchangeText\CorrectExchangeTextHandler;
use Prospecter\Core\Prospecting\Application\DetachForgottenContact\DetachForgottenContactHandler;
use Prospecter\Core\Prospecting\Application\DetachFromHolding\DetachFromHoldingHandler;
use Prospecter\Core\Prospecting\Application\DiscardAttachmentsOfErasedExchange\DiscardAttachmentsOfErasedExchangeHandler;
use Prospecter\Core\Prospecting\Application\DiscardRemovedAttachment\DiscardRemovedAttachmentHandler;
use Prospecter\Core\Prospecting\Application\DiscardRemovedLogo\DiscardRemovedLogoHandler;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\AttachmentDownloadResponse;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachment;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\DownloadAttachmentHandler;
use Prospecter\Core\Prospecting\Application\DownloadAttachment\KeptDownload;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchangeHandler;
use Prospecter\Core\Prospecting\Application\EraseExchangeLeftWithoutOrganization\EraseExchangeLeftWithoutOrganizationHandler;
use Prospecter\Core\Prospecting\Application\KeepExchangesOfForgottenContact\KeepExchangesOfForgottenContactHandler;
use Prospecter\Core\Prospecting\Application\KeptTimeline;
use Prospecter\Core\Prospecting\Application\ListExchangeFacts\ListExchangeFactsHandler;
use Prospecter\Core\Prospecting\Application\ListOrganizationFacts\ListOrganizationFactsHandler;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchangeHandler;
use Prospecter\Core\Prospecting\Application\NoteOrganizationExchange\NoteOrganizationExchangeHandler;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\PlanExchange\PlanExchangeHandler;
use Prospecter\Core\Prospecting\Application\PlanOrganizationExchange\PlanOrganizationExchangeHandler;
use Prospecter\Core\Prospecting\Application\PublishContactsLeftWithoutOrganization\PublishContactsLeftWithoutOrganizationHandler;
use Prospecter\Core\Prospecting\Application\PublishExchangeFacts\PublishExchangeFactsHandler;
use Prospecter\Core\Prospecting\Application\PublishOrganizationFacts\PublishOrganizationFactsHandler;
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
use Prospecter\Core\Prospecting\Application\WhichAreProspected\WhichAreProspectedHandler;
use Prospecter\Core\Prospecting\Application\WriteObservations\WriteObservationsHandler;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\KeptExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListExchangeFacts\ListExchangeFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\KeptOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\ListOrganizationFacts\ListOrganizationFacts;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\KeptProspected;
use Prospecter\Core\Prospecting\Contract\Service\WhichAreProspected\WhichAreProspected;
use Prospecter\Core\Prospecting\Domain\Service\ContactOrganizations;
use Prospecter\Core\Prospecting\Domain\Service\EmailUniqueness as ProspectingEmailUniqueness;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFigures;
use Prospecter\Core\Reporting\Application\ConsultFigures\ConsultFiguresHandler;
use Prospecter\Core\Reporting\Application\ConsultFigures\FiguresResponse;
use Prospecter\Core\Reporting\Application\ConsultFigures\KeptFigures;
use Prospecter\Core\Reporting\Application\FactRecorder;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Prospecter\Core\Reporting\Application\RebuildFigures\SourceFacts;
use Prospecter\Core\Reporting\Application\RecordContactFacts\RecordContactFactsHandler;
use Prospecter\Core\Reporting\Application\RecordExchangeFacts\RecordExchangeFactsHandler;
use Prospecter\Core\Reporting\Application\RecordOrganizationFacts\RecordOrganizationFactsHandler;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\SharedKernel\Application\CommandHandler;
use Prospecter\Core\SharedKernel\Application\EventHandler;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Application\FileStore;
use Prospecter\Core\SharedKernel\Application\GeoLocalizer;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingCommandHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingEventHandler;
use Prospecter\Core\SharedKernel\Application\RefusalTranslatingQueryHandler;
use Prospecter\Core\SharedKernel\Application\TransactionalCommandHandler;
use Prospecter\Core\SharedKernel\Application\TransactionalEventHandler;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Double\AccountsProspectors;
use Tests\Core\Double\AccountsReaders;
use Tests\Core\Double\AccountsSourceAccounts;
use Tests\Core\Double\InMemoryAccountListRetriever;
use Tests\Core\Double\InMemoryAccountRepository;
use Tests\Core\Double\InMemoryAgendaRetriever;
use Tests\Core\Double\InMemoryContactFactsRetriever;
use Tests\Core\Double\InMemoryContactPageRetriever;
use Tests\Core\Double\InMemoryDatabase;
use Tests\Core\Double\InMemoryExchangeDetailRetriever;
use Tests\Core\Double\InMemoryExchangeFactsRetriever;
use Tests\Core\Double\InMemoryExchangeRepository;
use Tests\Core\Double\InMemoryFactRepository;
use Tests\Core\Double\InMemoryFiguresRetriever;
use Tests\Core\Double\InMemoryFoundOrganizationRetriever;
use Tests\Core\Double\InMemoryMemberRepository;
use Tests\Core\Double\InMemoryMemberRetriever;
use Tests\Core\Double\InMemoryMyAccountRetriever;
use Tests\Core\Double\InMemoryOrganizationFactsRetriever;
use Tests\Core\Double\InMemoryOrganizationRepository;
use Tests\Core\Double\InMemoryOrganizationSheetRetriever;
use Tests\Core\Double\InMemoryPersonCardRetriever;
use Tests\Core\Double\InMemoryPersonRepository;
use Tests\Core\Double\InMemoryPrivateStorage;
use Tests\Core\Double\InMemoryProjectionVersions;
use Tests\Core\Double\InMemoryStorage;
use Tests\Core\Double\InMemoryTimelineRetriever;
use Tests\Core\Double\InMemoryUnitOfWork;
use Tests\Core\Double\KnownPlacesGeoLocation;
use Tests\Core\Double\NoRecordingsInFlight;
use Tests\Core\Double\PersonalDataFactsFromPersonalData;
use Tests\Core\Double\PersonalDataKnownContacts;
use Tests\Core\Double\ProspectingFactsFromProspecting;
use Tests\Core\Double\ProspectingOrganizations;
use Tests\Core\Double\SynchronousEvents;
use Tests\Core\Double\TimeOrderedUuids;

/**
 * The hexagon as the scenarios use it: the real handlers, wired to in-memory implementations of their
 * ports, called the way a delivery calls them: through the translation of their refusals, a command is sent to
 * its handler (a command bus in the API) within a unit of work its reactions join, and returns nothing, a query presents what it reads to the presenter
 * of the core keeping it as it is (ADR 0085).
 */
final class Application
{
    private const string FIRST_DAY = '2026-10-15 09:00';

    public readonly InMemoryDatabase $database;
    public readonly InMemoryOrganizationRepository $organizations;
    public readonly InMemoryFoundOrganizationRetriever $foundOrganizationRetriever;
    private readonly InMemoryOrganizationSheetRetriever $organizationSheetRetriever;
    private readonly InMemoryExchangeDetailRetriever $exchangeDetailRetriever;
    private readonly InMemoryTimelineRetriever $timelineRetriever;
    private readonly InMemoryAgendaRetriever $agendaRetriever;
    private readonly InMemoryMyAccountRetriever $myAccountRetriever;
    private readonly InMemoryMemberRetriever $memberRetriever;
    private readonly InMemoryPersonCardRetriever $personCardRetriever;
    private readonly InMemoryContactPageRetriever $contactPageRetriever;
    private readonly ConsultMembersHandler $consultMembersService;
    private readonly WhichAreContactsHandler $whichAreContactsService;
    private readonly WhichAreProspectedHandler $whichAreProspectedService;
    private readonly InMemoryOrganizationFactsRetriever $organizationFactsRetriever;
    private readonly InMemoryExchangeFactsRetriever $exchangeFactsRetriever;
    private readonly ListOrganizationFactsHandler $listOrganizationFactsService;
    private readonly ListExchangeFactsHandler $listExchangeFactsService;
    private readonly InMemoryContactFactsRetriever $contactFactsRetriever;
    private readonly ListContactFactsHandler $listContactFactsService;
    private readonly ListAccountsHandler $listAccountsService;
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
    private readonly InMemoryUnitOfWork $unitOfWork;
    private readonly FileStore $files;
    private readonly GeoLocalizer $geoLocalizer;
    private readonly PersonalDataEmailUniqueness $personEmails;
    private readonly ProspectingEmailUniqueness $organizationEmails;
    private readonly OrphanContacts $orphans;
    private readonly ContactOrganizations $contactOrganizations;
    public readonly InMemoryProjectionVersions $projectionVersions;
    private readonly AccountsSourceAccounts $sourceAccounts;
    private readonly InMemoryFiguresRetriever $figuresRetriever;
    private readonly FactRecorder $factRecorder;
    /** @var array<class-string, CommandHandler> */
    private array $handlers = [];
    private ?string $signedIn = null;
    private ?UseCaseRefused $refusal = null;
    private \DateTimeImmutable $now;
    private readonly TimeOrderedUuids $uuids;

    public function __construct()
    {
        $this->database = $database = new InMemoryDatabase();
        $this->now = new \DateTimeImmutable(self::FIRST_DAY);
        $this->uuids = new TimeOrderedUuids();
        $this->organizations = new InMemoryOrganizationRepository($database);
        $this->foundOrganizationRetriever = new InMemoryFoundOrganizationRetriever($database);
        $this->organizationSheetRetriever = new InMemoryOrganizationSheetRetriever($database);
        $this->exchangeDetailRetriever = new InMemoryExchangeDetailRetriever($database);
        $this->timelineRetriever = new InMemoryTimelineRetriever($database);
        $this->agendaRetriever = new InMemoryAgendaRetriever($database);
        $this->exchanges = new InMemoryExchangeRepository($database);
        $this->accounts = new InMemoryAccountRepository($database);
        $this->members = new InMemoryMemberRepository($database);
        $this->myAccountRetriever = new InMemoryMyAccountRetriever($database);
        $this->memberRetriever = new InMemoryMemberRetriever($database);
        $this->persons = new InMemoryPersonRepository($database);
        $this->personCardRetriever = new InMemoryPersonCardRetriever($database);
        $this->contactPageRetriever = new InMemoryContactPageRetriever($database);
        $this->consultMembersService = new ConsultMembersHandler($this->memberRetriever);
        $this->whichAreContactsService = new WhichAreContactsHandler($this->persons);
        $this->whichAreProspectedService = new WhichAreProspectedHandler($this->organizations);
        $this->organizationFactsRetriever = new InMemoryOrganizationFactsRetriever($database);
        $this->exchangeFactsRetriever = new InMemoryExchangeFactsRetriever($database);
        $this->listOrganizationFactsService = new ListOrganizationFactsHandler($this->organizationFactsRetriever);
        $this->listExchangeFactsService = new ListExchangeFactsHandler($this->exchangeFactsRetriever);
        $this->contactFactsRetriever = new InMemoryContactFactsRetriever($database);
        $this->listContactFactsService = new ListContactFactsHandler($this->contactFactsRetriever);
        $this->listAccountsService = new ListAccountsHandler(new InMemoryAccountListRetriever($database));
        $this->prospectors = new AccountsProspectors($this->consultMembersService);
        $this->readers = new AccountsReaders($this->consultMembersService);
        $this->knownContacts = new PersonalDataKnownContacts($this->whichAreContactsService);
        $this->prospectedOrganizations = new ProspectingOrganizations($this->whichAreProspectedService);
        $this->events = new SynchronousEvents();
        $this->unitOfWork = new InMemoryUnitOfWork($database);
        $this->storage = new InMemoryStorage();
        $this->privateStorage = new InMemoryPrivateStorage();
        $this->geoLocation = new KnownPlacesGeoLocation();
        $this->files = new FileStore($this->storage);
        $this->geoLocalizer = new GeoLocalizer($this->geoLocation);
        $this->personEmails = new PersonalDataEmailUniqueness($this->persons);
        $this->organizationEmails = new ProspectingEmailUniqueness($this->organizations);
        $this->orphans = new OrphanContacts($this->organizations);
        $this->contactOrganizations = new ContactOrganizations($this->organizations);
        $this->projectionVersions = new InMemoryProjectionVersions($database);
        $this->sourceAccounts = new AccountsSourceAccounts($this->listAccountsService, $this->consultMembersService);
        $this->figuresRetriever = new InMemoryFiguresRetriever($database);
        $this->factRecorder = new FactRecorder(new InMemoryFactRepository($database), $this->projectionVersions);

        foreach ($this->translators() as $translator) {
            $this->events->translateWith($translator, calledThrough: new RefusalTranslatingEventHandler(new TransactionalEventHandler($translator, $this->unitOfWork)));
        }

        foreach ($this->reactions() as $reaction) {
            $this->alsoReactWith($reaction);
        }

        foreach ($this->commandHandlers() as $handler) {
            $this->handlers[$this->commandOf($handler)] = new RefusalTranslatingCommandHandler(new TransactionalCommandHandler($handler, $this->unitOfWork));
        }

        $this->handlers[RebuildFigures::class] = new RefusalTranslatingCommandHandler($this->rebuildFigures());
    }

    /**
     * A reaction subscribed after those already there, called the way the worker of the API calls it: through
     * the translation of its refusals, in a unit of work of its own, once the command that published succeeded.
     */
    public function alsoReactWith(EventHandler $reaction): void
    {
        $this->events->subscribe($reaction, calledThrough: new RefusalTranslatingEventHandler(new TransactionalEventHandler($reaction, $this->unitOfWork)));
    }

    /**
     * The next command is refused when it commits, after its handler ran, as a write whose condition no longer holds.
     */
    public function refuseTheNextCommit(BusinessException $refusal): void
    {
        $this->unitOfWork->refuseTheNextCommit($refusal);
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
        $wasOpen = $this->unitOfWork->isOpen();
        $this->attempt(function () use ($handler, $command): void {
            $this->events->deliverAfter(static fn () => $handler($command));
        });

        if (!$wasOpen && $this->unitOfWork->isOpen()) {
            throw new \LogicException(sprintf('%s left a unit of work open', $command::class));
        }
    }

    /**
     * An event delivered once more to its reactions, as a bus delivering at least once may do.
     */
    public function publishAgain(object $event): void
    {
        $this->attempt(function () use ($event): void {
            $this->events->deliverAfter(fn () => $this->events->publish($event));
        });
    }

    /**
     * A recording that read the live versions before a rebuild activated a new one, writing in the former, as the
     * worker may.
     */
    public function recordIn(int $version, Fact $fact): void
    {
        $this->factRecorder->recordIn($version, $fact);
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

    public function consultUpcomingExchanges(ConsultUpcomingExchanges $query): ?Timeline
    {
        $kept = new KeptTimeline();

        return $this->ask(new ConsultUpcomingExchangesHandler($this->timelineRetriever, $this->prospectors, $this->contactOrganizations), $query, $kept) ? $kept->timeline : null;
    }

    public function consultOrganizationUpcomingExchanges(ConsultOrganizationUpcomingExchanges $query): ?Timeline
    {
        $kept = new KeptTimeline();

        return $this->ask(new ConsultOrganizationUpcomingExchangesHandler($this->timelineRetriever, $this->organizationSheetRetriever, $this->prospectors), $query, $kept) ? $kept->timeline : null;
    }

    public function consultOverlappingExchanges(ConsultOverlappingExchanges $query): ?OverlappingExchanges
    {
        $kept = new KeptOverlappingExchanges();

        return $this->ask(new ConsultOverlappingExchangesHandler($this->agendaRetriever, $this->prospectors), $query, $kept) ? $kept->overlapping : null;
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

    public function consultFigures(ConsultFigures $query): ?FiguresResponse
    {
        $kept = new KeptFigures();

        return $this->ask(new ConsultFiguresHandler($this->sourceAccounts, $this->projectionVersions, $this->figuresRetriever), $query, $kept) ? $kept->figures : null;
    }

    /**
     * Events delivered to their reactions at the same time, as invocations of the worker running together: each
     * reaction reads what it needs before any writes.
     */
    public function publishAtTheSameTime(object ...$events): void
    {
        $deliveries = array_map(fn (object $event): \Fiber => new \Fiber(fn () => $this->publishAgain($event)), $events);

        foreach ($deliveries as $delivery) {
            $delivery->start();
        }

        while (array_any($deliveries, static fn (\Fiber $delivery): bool => !$delivery->isTerminated())) {
            foreach (array_filter($deliveries, static fn (\Fiber $delivery): bool => $delivery->isSuspended()) as $delivery) {
                $delivery->resume();
            }
        }
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
            new RemoveLogoHandler($this->organizations, $this->events),
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
            new PlanExchangeHandler($this->exchanges, $this->prospectors, $this->contactOrganizations, $this->events),
            new PlanOrganizationExchangeHandler($this->exchanges, $this->organizations, $this->contactOrganizations, $this->events),
            new CorrectExchangeHandler($this->exchanges, $this->events),
            new CorrectExchangeTextHandler($this->exchanges, $this->events),
            new EraseExchangeHandler($this->exchanges, $this->events),
            new AttachFileHandler($this->exchanges, $this->privateStorage, $this->events),
            new RemoveAttachmentHandler($this->exchanges, $this->events),
        ];
    }

    /**
     * The services a context offers the others refuse nothing (ADR 0089): they are called as an anticorruption
     * layer calls them.
     *
     * @return array<string, MemberResponse>
     */
    public function consultMembers(ConsultMembers $query): array
    {
        $kept = new KeptMembers();
        ($this->consultMembersService)($query, $kept);

        return $kept->members;
    }

    /**
     * @return list<string>
     */
    public function whichAreContacts(WhichAreContacts $query): array
    {
        $kept = new KeptContacts();
        ($this->whichAreContactsService)($query, $kept);

        return $kept->personIds;
    }

    /**
     * @return list<string>
     */
    public function whichAreProspected(WhichAreProspected $query): array
    {
        $kept = new KeptProspected();
        ($this->whichAreProspectedService)($query, $kept);

        return $kept->organizationIds;
    }

    public function listOrganizationFacts(ListOrganizationFacts $query): KeptOrganizationFacts
    {
        $kept = new KeptOrganizationFacts();
        ($this->listOrganizationFactsService)($query, $kept);

        return $kept;
    }

    public function listExchangeFacts(ListExchangeFacts $query): KeptExchangeFacts
    {
        $kept = new KeptExchangeFacts();
        ($this->listExchangeFactsService)($query, $kept);

        return $kept;
    }

    public function listContactFacts(ListContactFacts $query): KeptContactFacts
    {
        $kept = new KeptContactFacts();
        ($this->listContactFactsService)($query, $kept);

        return $kept;
    }

    public function listAccounts(ListAccounts $query): KeptAccounts
    {
        $kept = new KeptAccounts();
        ($this->listAccountsService)($query, $kept);

        return $kept;
    }

    /**
     * The translators publishing the domain events of a context for the others, the facts first, called within the
     * command as the event bus of the API calls them (`EventHandlersSubscribedTest` fails when one is missing).
     *
     * @return list<EventHandler>
     */
    private function translators(): array
    {
        return [
            new PublishOrganizationFactsHandler($this->organizationFactsRetriever, $this->events),
            new PublishExchangeFactsHandler($this->exchangeFactsRetriever, $this->events),
            new PublishContactFactsHandler($this->contactFactsRetriever, $this->events),
            new PublishAccountOpenedHandler($this->events),
            new PublishMemberRegisteredHandler($this->events),
            new PublishContactCollectedHandler($this->events),
            new PublishContactForgottenHandler($this->events),
            new PublishContactsLeftWithoutOrganizationHandler($this->events),
        ];
    }

    /**
     * Every other event handler of the core: the reactions, internal or of the other contexts, run in the scenarios
     * after the command as the worker of the API runs the subscriptions.
     *
     * @return list<EventHandler>
     */
    private function reactions(): array
    {
        return [
            new AttachCollectedContactHandler($this->organizations, $this->events),
            new DetachForgottenContactHandler($this->organizations, $this->events, $this->orphans),
            new KeepExchangesOfForgottenContactHandler($this->exchanges, $this->events),
            new ForgetContactsLeftWithoutOrganizationHandler($this->persons, $this->readers, $this->events),
            new RegisterTheFounderHandler($this->members),
            new CollectTheFounderProfileHandler($this->persons),
            new CollectTheMemberProfileHandler($this->persons),
            new StopKeepingExchangesHandler($this->exchanges, $this->events),
            new EraseExchangeLeftWithoutOrganizationHandler($this->exchanges, $this->events),
            new DiscardRemovedLogoHandler($this->storage),
            new DiscardRemovedAttachmentHandler($this->privateStorage),
            new DiscardAttachmentsOfErasedExchangeHandler($this->privateStorage),
            new RecordOrganizationFactsHandler($this->factRecorder),
            new RecordExchangeFactsHandler($this->factRecorder),
            new RecordContactFactsHandler($this->factRecorder),
        ];
    }

    /**
     * The rebuild commits each of its steps in a unit of its own: no unit of work around it (ADR 0103).
     */
    private function rebuildFigures(): RebuildFiguresHandler
    {
        return new RebuildFiguresHandler(
            $this->projectionVersions,
            new NoRecordingsInFlight(),
            new SourceFacts($this->sourceAccounts, new ProspectingFactsFromProspecting($this->listOrganizationFactsService, $this->listExchangeFactsService), new PersonalDataFactsFromPersonalData($this->listContactFactsService)),
            $this->factRecorder,
            $this->unitOfWork,
        );
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
            new RefusalTranslatingQueryHandler($handler)($query, $output);

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
