<?php

declare(strict_types=1);

namespace Tests\Core\Acceptance\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Campaigns\Application\AddRecipient\AddRecipient;
use Prospecter\Core\Campaigns\Application\AddRecipientsByTags\AddRecipientsByTags;
use Prospecter\Core\Campaigns\Application\CampaignSheet;
use Prospecter\Core\Campaigns\Application\ConsultCampaign\ConsultCampaign;
use Prospecter\Core\Campaigns\Application\DiscardCampaign\DiscardCampaign;
use Prospecter\Core\Campaigns\Application\DismissRecipient\DismissRecipient;
use Prospecter\Core\Campaigns\Application\EraseCampaign\EraseCampaign;
use Prospecter\Core\Campaigns\Application\FreezeCampaign\FreezeCampaign;
use Prospecter\Core\Campaigns\Application\ListCampaigns\ListCampaigns;
use Prospecter\Core\Campaigns\Application\PrepareCampaign\PrepareCampaign;
use Prospecter\Core\Campaigns\Application\RebuildTags\RebuildTags;
use Prospecter\Core\Campaigns\Application\RecipientInSheet;
use Prospecter\Core\Campaigns\Application\RemoveRecipient\RemoveRecipient;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\CampaignMessage;
use Prospecter\Core\Campaigns\Application\RenderCampaignMessage\RenderCampaignMessage;
use Prospecter\Core\Campaigns\Application\ReviseCampaign\ReviseCampaign;
use Prospecter\Core\Campaigns\Application\SearchContactsByTags\SearchContactsByTags;
use Prospecter\Core\Campaigns\Application\StartCampaign\StartCampaign;
use Prospecter\Core\Campaigns\Application\StopCampaign\StopCampaign;
use Prospecter\Core\Campaigns\Contract\Service\NameCampaigns\NameCampaigns;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\PendingRecipientsResponse;
use Prospecter\Core\Campaigns\Contract\Service\WhichRecipientsArePending\WhichRecipientsArePending;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\PersonalData\Application\ChangePersonContactDetails\ChangePersonContactDetails;
use Prospecter\Core\PersonalData\Application\ConsultPersons\ConsultPersons;
use Prospecter\Core\PersonalData\Application\PersonCard;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ConsultExchange;
use Prospecter\Core\Prospecting\Application\ConsultExchange\ExchangeDetail;
use Prospecter\Core\Prospecting\Application\ConsultTimeline\ConsultTimeline;
use Prospecter\Core\Prospecting\Application\EraseExchange\EraseExchange;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\NoteCampaignExchange\NoteCampaignExchange;
use Prospecter\Core\Prospecting\Application\NoteExchange\NoteExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\Core\Acceptance\Expect;
use Tests\Core\Acceptance\Vocabulary;

/**
 * The campaigns a team prepares, whom they target and the message they render for each contact (Campaigns,
 * ADR 0144).
 */
final class CampaignContext implements Context
{
    use SharesTheScenario;

    private const array CHANNELS = ['E-mail' => 'email', 'Appel' => 'call', 'SMS' => 'sms', 'LinkedIn' => 'linkedIn'];
    private const array STATUSES = ['draft' => 'brouillon', 'frozen' => 'figée', 'started' => 'démarrée', 'stopped' => 'arrêtée', 'ended' => 'terminée'];
    private const array RECIPIENT_STATUSES = ['pending' => 'à traiter', 'erased' => 'plus à traiter', 'processed' => 'traité', 'dismissed' => 'écarté'];
    private const string NOBODY_CARRIES = '#PERSONNE';
    private const string A_MESSAGE = 'Bonjour {prénom}';
    private const string REVISED_MESSAGE = 'Bonjour {prénom}, je reviens vers vous';
    private const string SOME_TAGS = '#ESN';

    /** @var array<string, string> the identities of the campaigns, by their names */
    private array $campaignIds = [];
    private ?string $current = null;
    private ?ReviseCampaign $revision = null;
    private ?string $noted = null;
    private ?string $notedWith = null;
    private ?PendingRecipientsResponse $pending = null;
    private ?ExchangeDetail $detail = null;
    private ?string $other = null;
    /** @var array<string, string> */
    private array $campaignNames = [];
    private ?CampaignMessage $rendered = null;
    /** @var list<CampaignSheet> */
    private array $listed = [];
    /** @var array<string, int> */
    private array $targeted = [];

    #[When('je prépare la campagne :name :')]
    public function iPrepareTheCampaign(string $name, TableNode $table): void
    {
        $values = $table->getRowsHash();
        $this->prepare($name, $values['canal'], $values['tags'], $values['message'], $values['objet'] ?? null);
    }

    #[When('je prépare la campagne :name avec le canal :channel et les tags :tags')]
    public function iPrepareTheCampaignBy(string $name, string $channel, string $tags): void
    {
        $this->prepare($name, $channel, $tags, self::A_MESSAGE);
    }

    #[When('je prépare une campagne avec le canal :channel')]
    public function iPrepareACampaignBy(string $channel): void
    {
        $this->prepare('Campagne', $channel, self::SOME_TAGS, self::A_MESSAGE);
    }

    #[When('je prépare la campagne :name sans tag')]
    public function iPrepareTheCampaignWithoutTag(string $name): void
    {
        $this->prepare($name, 'E-mail', '', self::A_MESSAGE);
    }

    #[When('je prépare une campagne dont le nom fait :length caractères')]
    public function iPrepareACampaignNamedAtLength(int $length): void
    {
        $this->prepare(str_repeat('a', $length), 'E-mail', self::SOME_TAGS, self::A_MESSAGE);
    }

    #[When('je prépare une campagne dont le message fait :length caractères')]
    public function iPrepareACampaignWithAMessageOf(int $length): void
    {
        $this->prepare('Campagne', 'E-mail', self::SOME_TAGS, str_repeat('é', $length));
    }

    #[When('je prépare la campagne :name par SMS avec l\'objet :subject')]
    public function iPrepareTheCampaignBySmsWithASubject(string $name, string $subject): void
    {
        $this->prepare($name, 'SMS', self::SOME_TAGS, self::A_MESSAGE, $subject);
    }

    #[Given('la campagne :name en brouillon')]
    public function theDraft(string $name): void
    {
        $this->arrange($name, 'E-mail', self::SOME_TAGS, self::A_MESSAGE);
    }

    #[Given('la campagne :name en brouillon cible les tags :tags')]
    public function theDraftTargeting(string $name, string $tags): void
    {
        $this->arrange($name, 'E-mail', $tags, self::A_MESSAGE);
    }

    #[Given('la campagne :name en brouillon cible les tags :tags par :channel')]
    public function theDraftTargetingBy(string $name, string $tags, string $channel): void
    {
        $this->arrange($name, $channel, $tags, self::A_MESSAGE);
    }

    #[Given('la campagne :name a le message :message')]
    public function theCampaignHasTheMessage(string $name, string $message): void
    {
        $this->arrange($name, 'E-mail', self::SOME_TAGS, $message);
    }

    #[Given('la campagne :name a l\'objet :subject et le message :message')]
    public function theCampaignHasTheSubjectAndMessage(string $name, string $subject, string $message): void
    {
        $this->arrange($name, 'E-mail', self::SOME_TAGS, $message, $subject);
    }

    #[Given('un autre prospecteur de :account a préparé la campagne :name')]
    public function anotherProspectorPrepared(string $account, string $name): void
    {
        $author = $this->scenario->aMemberOf('un autre prospecteur', $account, Role::User, Role::Prospector, Role::PersonalData);
        $this->arrange($name, 'E-mail', self::SOME_TAGS, self::A_MESSAGE, null, $author->accountId(), $author->id());
    }

    #[Given('un prospecteur de :account a préparé la campagne :name')]
    public function aProspectorPrepared(string $account, string $name): void
    {
        $accountId = $this->scenario->accountIdOf($account);
        $this->arrange($name, 'E-mail', self::SOME_TAGS, self::A_MESSAGE, null, $accountId, $this->scenario->prospectorOf($accountId)->id);
    }

    #[When(':contact reçoit l\'adresse e-mail :email')]
    public function theContactGetsTheEmail(string $contact, string $email): void
    {
        $this->application->dispatch(new ChangePersonContactDetails($this->application->signedIn(), $this->scenario->personIdOf($contact), $email, null, null, [], $this->application->now()));
    }

    #[When('je change ses tags en :tags, son canal en :channel et son message')]
    public function iReviseIt(string $tags, string $channel): void
    {
        $this->revision = new ReviseCampaign($this->application->signedIn(), $this->currentId(), $this->currentName(), self::CHANNELS[$channel], explode(' ', $tags), self::REVISED_MESSAGE, null, $this->application->now());
        $this->application->dispatch($this->revision);
    }

    #[When('je supprime ce brouillon')]
    public function iDiscardIt(): void
    {
        $this->application->dispatch(new DiscardCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[Given('la campagne :name en brouillon, canal :channel, tags :tags')]
    public function theDraftByWithTags(string $name, string $channel, string $tags): void
    {
        $this->arrange($name, $channel, $tags, self::A_MESSAGE);
    }

    #[Given('la campagne :name en brouillon ne cible personne')]
    public function theDraftTargetingNobody(string $name): void
    {
        $this->arrange($name, 'E-mail', self::NOBODY_CARRIES, self::A_MESSAGE);
    }

    #[Given('la campagne :name est figée')]
    public function theFrozenCampaign(string $name): void
    {
        $this->iFreezeTheCampaign($name);
        $this->itIsFrozen();
    }

    #[Given('la campagne :name est figée avec :one et :other')]
    public function theCampaignFrozenWith(string $name, string $one, string $other): void
    {
        $this->theFrozenCampaign($name);
        $this->iAddTheContact($other);
        Expect::same([$this->scenario->personIdOf($one), $this->scenario->personIdOf($other)], array_keys($this->sheet()->recipients));
    }

    #[Given('la campagne :name figée, canal :channel, avec :contact')]
    public function theCampaignFrozenByWith(string $name, string $channel, string $contact): void
    {
        $this->application->dispatch(new RebuildTags(null));
        $this->arrange($name, $channel, self::SOME_TAGS, self::A_MESSAGE);
        $this->theFrozenCampaign($name);
        $this->itsRecipientsAre($contact);
    }

    #[Given('la campagne :name démarrée, canal :channel, avec :one et :other')]
    public function theCampaignStartedByWith(string $name, string $channel, string $one, string $other): void
    {
        $this->application->dispatch(new RebuildTags(null));
        $this->arrange($name, $channel, self::SOME_TAGS, self::A_MESSAGE);
        $this->theFrozenCampaign($name);
        $this->itsRecipientsAreInAnyOrder($one, $other);
        $this->theStartedCampaign($name);
    }

    #[Given('la campagne :name démarrée, canal :channel, avec :one, :two et :three')]
    public function theCampaignStartedByWithThree(string $name, string $channel, string $one, string $two, string $three): void
    {
        $this->application->dispatch(new RebuildTags(null));
        $this->arrange($name, $channel, self::SOME_TAGS, self::A_MESSAGE);
        $this->theFrozenCampaign($name);
        $this->itsRecipientsAreInAnyOrder($one, $two, $three);
        $this->theStartedCampaign($name);
    }

    #[When('je note pour :contact dans la campagne :name l\'échange :')]
    public function iNoteForTheRecipientTheExchange(string $contact, string $name, TableNode $table): void
    {
        $row = $table->getRowsHash();
        $this->noteForTheRecipient($contact, $name, Vocabulary::frenchMoment($row['moment']), $row['texte'], missed: false);
    }

    #[When('je note pour :contact dans la campagne :name un échange')]
    public function iNoteForTheRecipientAnExchange(string $contact, string $name): void
    {
        $this->noteForTheRecipient($contact, $name, $this->application->now(), self::A_MESSAGE, missed: false);
    }

    #[When('je note pour :contact dans la campagne :name un appel manqué')]
    public function iNoteForTheRecipientAMissedCall(string $contact, string $name): void
    {
        $this->noteForTheRecipient($contact, $name, $this->application->now(), null, missed: true);
    }

    #[Given(':contact est traitée dans la campagne :name')]
    public function theRecipientIsProcessed(string $contact, string $name): void
    {
        if (null === $this->noted) {
            $this->iNoteForTheRecipientAnExchange($contact, $name);
        }

        $this->expectTheRecipient($contact, $name, ['status' => 'traité', 'missed' => false, 'reason' => null]);
    }

    #[Given(':contact est traitée dans la campagne :name, appel manqué')]
    public function theRecipientIsProcessedMissed(string $contact, string $name): void
    {
        if (null === $this->noted) {
            $this->iNoteForTheRecipientAMissedCall($contact, $name);
        }

        $this->expectTheRecipient($contact, $name, ['status' => 'traité', 'missed' => true, 'reason' => null]);
    }

    #[When('j\'écarte :contact de la campagne :name, pour :reason')]
    public function iDismissTheRecipientFor(string $contact, string $name, string $reason): void
    {
        $this->dismiss($contact, $name, $reason);
    }

    #[When('j\'écarte :contact de la campagne :name')]
    public function iDismissTheRecipient(string $contact, string $name): void
    {
        $this->dismiss($contact, $name, null);
    }

    #[Given(':contact est écarté de la campagne :name')]
    public function theRecipientIsDismissed(string $contact, string $name): void
    {
        $this->dismiss($contact, $name, null);
        $this->expectTheRecipient($contact, $name, ['status' => 'écarté', 'missed' => false, 'reason' => null]);
    }

    #[Then(':contact est écarté dans la campagne :name, pour :reason')]
    public function theRecipientIsDismissedFor(string $contact, string $name, string $reason): void
    {
        $this->expectTheRecipient($contact, $name, ['status' => 'écarté', 'missed' => false, 'reason' => $reason]);
    }

    #[Then('/^la campagne "(?<name>[^"]+)" est terminée le (?<moment>.+)$/')]
    public function theCampaignIsEndedOn(string $name, string $moment): void
    {
        $this->current = $name;
        $sheet = $this->sheet();

        Expect::same(['ended', Vocabulary::frenchMoment($moment)->getTimestamp()], [$sheet->status, $sheet->endedAt?->getTimestamp()]);
    }

    #[Then('l\'avancement de la campagne :name est :processed traité sur :recipients, :dismissed écarté')]
    public function theProgressOfTheCampaignIs(string $name, int $processed, int $recipients, int $dismissed): void
    {
        $this->current = $name;
        $this->itsProgressIs($processed, $recipients, $dismissed);
    }

    #[Then('son avancement est :processed traité sur :recipients, :dismissed écarté')]
    public function itsProgressIs(int $processed, int $recipients, int $dismissed): void
    {
        $sheet = $this->sheet();

        Expect::same([$processed, $recipients, $dismissed], [$sheet->processed, count($sheet->recipients), $sheet->dismissed]);
    }

    #[Then('c\'est un appel émis')]
    public function itIsAnOutboundCall(): void
    {
        $exchange = $this->notedExchange();

        Expect::same(['call', 'outbound', 'done'], [$exchange->channel, $exchange->direction, $exchange->status]);
    }

    #[Then('la timeline de :contact montre l\'appel, de la campagne :name')]
    public function theTimelineShowsTheCallOfTheCampaign(string $contact, string $name): void
    {
        $exchange = $this->notedExchange();

        Expect::same(['call', $this->campaignIds[$name], $name], [$exchange->channel, $exchange->campaignId, $exchange->campaignName]);
    }

    #[Then('le fait publié de cet échange nomme la campagne :name et le contact :contact')]
    public function theFactsOfTheExchangeNameTheCampaign(string $name, string $contact): void
    {
        $exchangeId = $this->noted ?? throw new \LogicException('No exchange noted in the scenario');
        $last = array_last(array_filter($this->application->events->published(ExchangeFactsChanged::class), static fn (ExchangeFactsChanged $facts): bool => $facts->exchangeId === $exchangeId));

        Expect::that(null !== $last, 'No facts of the exchange were published');
        Expect::same([$this->campaignIds[$name], $this->scenario->personIdOf($contact)], [$last?->campaignId, $last?->contactId]);
    }

    #[Given('j\'ai noté pour :contact dans la campagne :name un e-mail émis')]
    public function iNotedForTheRecipientAnEmail(string $contact, string $name): void
    {
        $this->iNoteForTheRecipientAnExchange($contact, $name);
        $exchange = $this->notedExchange();

        Expect::same(['email', 'outbound'], [$exchange->channel, $exchange->direction]);
    }

    #[Then(':contact est traitée, avec un lien vers son échange')]
    public function theRecipientIsProcessedLinkingItsExchange(string $contact): void
    {
        $recipient = $this->recipient($contact);

        Expect::same(['traité', $this->notedId()], [self::RECIPIENT_STATUSES[$recipient->status], $recipient->exchangeId]);
    }

    #[Then(':contact est traitée, sans lien vers son échange')]
    public function theRecipientIsProcessedLinkingNoExchange(string $contact): void
    {
        $recipient = $this->recipient($contact);

        Expect::same(['traité', null], [self::RECIPIENT_STATUSES[$recipient->status], $recipient->exchangeId]);
    }

    #[When('je consulte l\'échange de :contact')]
    public function iConsultTheExchangeOf(string $contact): void
    {
        $this->detail = $this->application->consultExchange(new ConsultExchange($this->application->signedIn(), $this->notedIdWith($contact), $this->application->now()));
    }

    #[Given('j\'ai noté un autre e-mail envoyé à :contact, hors de la campagne')]
    public function iNotedAnotherEmail(string $contact): void
    {
        $this->other = $this->application->newIdentity();
        $this->application->dispatch(new NoteExchange($this->application->signedIn(), $this->other, $this->scenario->personIdOf($contact), 'email', 'outbound', $this->application->now(), null, false, $this->application->now()));
        $this->scenario->itIsNotRefused();
    }

    #[When('je supprime cet autre échange')]
    public function iEraseTheOtherExchange(): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), $this->other ?? throw new \LogicException('No other exchange noted'), $this->application->now()));

        Expect::same([], array_map(static fn (\Throwable $failure): string => $failure->getMessage(), $this->application->events->failuresOf(ExchangeFactsChanged::class)));
    }

    #[Then('il vient de la campagne :name, avec un lien vers elle')]
    public function itComesFromTheCampaign(string $name): void
    {
        $detail = $this->detail ?? throw new \LogicException(sprintf('The exchange was refused: %s', $this->application->refusal()?->getMessage()));

        Expect::same([$this->campaignIdOf($name), $name], [$detail->campaignId, $detail->campaignName]);
    }

    #[When('je supprime l\'échange de :contact')]
    public function iEraseTheExchangeOf(string $contact): void
    {
        $this->application->dispatch(new EraseExchange($this->application->signedIn(), $this->notedIdWith($contact), $this->application->now()));
    }

    #[Then(':contact est à traiter dans la campagne :name')]
    public function theRecipientIsPending(string $contact, string $name): void
    {
        $this->current = $name;
        $recipient = $this->recipient($contact);

        Expect::same(['à traiter', null], [self::RECIPIENT_STATUSES[$recipient->status], $recipient->exchangeId]);
    }

    #[Given('la campagne :name est terminée')]
    public function theEndedCampaign(string $name): void
    {
        $this->current = $name;
        Expect::same('ended', $this->sheet()->status);
    }

    #[Then('la campagne :name est de nouveau démarrée')]
    public function theCampaignIsStartedAgain(string $name): void
    {
        $this->current = $name;
        $sheet = $this->sheet();

        Expect::same(['started', null], [$sheet->status, $sheet->endedAt]);
    }

    #[Then('la campagne :name est toujours arrêtée')]
    public function theCampaignIsStillStopped(string $name): void
    {
        $this->current = $name;
        Expect::same('stopped', $this->sheet()->status);
    }

    #[Then('l\'échange de :contact reste dans sa timeline, sans campagne')]
    public function theExchangeStaysWithoutCampaign(string $contact): void
    {
        $exchange = $this->notedExchange();

        Expect::same([$contact, null], [$this->notedWith, $exchange->campaignName]);
    }

    #[Then('le fait publié de cet échange, absent, nomme la campagne :name et le contact :contact')]
    public function theTerminalFactsOfTheExchangeNameTheCampaign(string $name, string $contact): void
    {
        $exchangeId = $this->notedId();
        $last = array_last(array_filter($this->application->events->published(ExchangeFactsChanged::class), static fn (ExchangeFactsChanged $facts): bool => $facts->exchangeId === $exchangeId));

        Expect::that(null !== $last, 'No facts of the exchange were published');
        Expect::same([false, $this->campaignIds[$name], $this->scenario->personIdOf($contact)], [$last?->present, $last?->campaignId, $last?->contactId]);
    }

    public function campaignIdOf(string $name): string
    {
        return $this->campaignIds[$name] ?? throw new \LogicException(sprintf('No campaign "%s" in the scenario', $name));
    }

    #[When('un autre contexte demande à Campaigns, de :account, lesquels sont des destinataires à traiter de :name :')]
    public function anotherContextAsksThePendingRecipients(string $account, string $name, TableNode $contacts): void
    {
        $this->pending = $this->application->whichRecipientsArePending(new WhichRecipientsArePending(
            $this->scenario->accountIdOf($account)->value,
            $this->campaignIdOf($name),
            array_map($this->scenario->personIdOf(...), $contacts->getColumn(0)),
        ));
    }

    #[Then('Campaigns répond que :name est démarrée, par :channel, avec les destinataires :one et :other, :pending à traiter')]
    public function campaignsAnswersThePendingRecipients(string $name, string $channel, string $one, string $other, string $pending): void
    {
        Expect::same(
            [$this->campaignIdOf($name), $channel, true, [$this->scenario->personIdOf($one), $this->scenario->personIdOf($other)], [$this->scenario->personIdOf($pending)]],
            [$this->pending?->campaignId, $this->pending?->channel, $this->pending?->started, $this->pending?->recipientIds, $this->pending?->pendingIds],
        );
    }

    #[Then('Campaigns ne répond aucune campagne')]
    public function campaignsAnswersNoCampaign(): void
    {
        Expect::same(null, $this->pending);
    }

    #[When('un autre contexte demande à Campaigns, de :account, le nom de :name et d\'une campagne inconnue')]
    public function anotherContextAsksTheNames(string $account, string $name): void
    {
        $this->campaignNames = $this->application->nameCampaigns(new NameCampaigns($this->scenario->accountIdOf($account)->value, [$this->campaignIdOf($name), $this->application->newIdentity()]));
    }

    #[Then('Campaigns nomme seulement :name')]
    public function campaignsNamesOnly(string $name): void
    {
        Expect::same([$this->campaignIdOf($name) => $name], $this->campaignNames);
    }

    #[Then('Campaigns ne nomme aucune campagne')]
    public function campaignsNamesNoCampaign(): void
    {
        Expect::same([], $this->campaignNames);
    }

    #[When('je démarre la campagne :name')]
    public function iStartTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new StartCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[Given('la campagne :name est démarrée')]
    public function theStartedCampaign(string $name): void
    {
        $this->iStartTheCampaign($name);
        Expect::same('started', $this->sheet()->status);
    }

    #[When('j\'arrête la campagne :name')]
    public function iStopTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new StopCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[Given('la campagne :name est arrêtée')]
    public function theStoppedCampaign(string $name): void
    {
        $this->theStartedCampaign($name);
        $this->iStopTheCampaign($name);
        Expect::same('stopped', $this->sheet()->status);
    }

    #[When('l\'exploitation efface la campagne :name')]
    public function theOperationsEraseTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new EraseCampaign($this->currentId()));
    }

    #[Then('/^elle est démarrée le (?<moment>.+)$/')]
    public function itIsStartedOn(string $moment): void
    {
        $sheet = $this->sheet();

        Expect::same(['started', Vocabulary::frenchMoment($moment)->getTimestamp()], [$sheet->status, $sheet->startedAt?->getTimestamp()]);
    }

    #[Then('/^elle est arrêtée le (?<moment>.+)$/')]
    public function itIsStoppedOn(string $moment): void
    {
        $sheet = $this->sheet();

        Expect::same(['stopped', Vocabulary::frenchMoment($moment)->getTimestamp()], [$sheet->status, $sheet->stoppedAt?->getTimestamp()]);
    }

    #[Then('elle est toujours dans la liste des campagnes')]
    public function itIsStillListed(): void
    {
        $this->iListTheCampaigns();
        Expect::same([$this->currentName()], array_map(static fn (CampaignSheet $campaign): string => $campaign->name, $this->listed));
    }

    #[Then('dans la campagne :name, un contact effacé n\'est plus à traiter')]
    public function anErasedContactIsNoLongerToHandle(string $name): void
    {
        $this->anErasedContactIs($name, 'plus à traiter');
    }

    #[Then('/^dans la campagne "(?<name>[^"]+)", un contact effacé est (?<status>.+)$/')]
    public function anErasedContactIs(string $name, string $status): void
    {
        $this->theErasedContactsAre($name, [$status]);
    }

    #[Then('/^dans la campagne "(?<name>[^"]+)", les contacts effacés sont « (?<one>[^»]+) » et « (?<other>[^»]+) »$/')]
    public function theErasedContactsAreBoth(string $name, string $one, string $other): void
    {
        $this->theErasedContactsAre($name, [$one, $other]);
    }

    /**
     * Without their names: PersonalData reads none of them.
     *
     * @param list<string> $statuses in any order
     */
    private function theErasedContactsAre(string $name, array $statuses): void
    {
        $this->current = $name;
        $erased = array_filter($this->sheet()->recipients, static fn (RecipientInSheet $recipient): bool => $recipient->erased);
        $read = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), array_map(strval(...), array_keys($erased)))) ?? [];
        $described = array_values(array_map(self::described(...), $erased));
        sort($statuses);
        sort($described);

        Expect::same([$statuses, []], [$described, $read]);
    }

    #[Then('son destinataire est « Contact effacé », plus à traiter')]
    public function itsRecipientIsAnErasedContact(): void
    {
        $recipients = $this->sheet()->recipients;
        $read = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), array_map(strval(...), array_keys($recipients)))) ?? [];

        Expect::same(['plus à traiter'], array_values(array_map(static fn (RecipientInSheet $recipient): string => self::RECIPIENT_STATUSES[$recipient->status], $recipients)));
        Expect::same([], $read);
    }

    #[When('je fige la campagne :name')]
    public function iFreezeTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->dispatch(new FreezeCampaign($this->application->signedIn(), $this->currentId(), $this->application->now()));
    }

    #[When('je change ses tags en :tags')]
    public function iChangeItsTags(string $tags): void
    {
        $sheet = $this->sheet();
        $this->application->dispatch(new ReviseCampaign($this->application->signedIn(), $sheet->id, $sheet->name, $sheet->channel, self::listed($tags), $sheet->message, $sheet->subject, $this->application->now()));
    }

    #[When('je la renomme :name et change son message')]
    public function iRenameItAndChangeItsMessage(string $name): void
    {
        $sheet = $this->sheet();
        $this->campaignIds[$name] = $sheet->id;
        $this->revision = new ReviseCampaign($this->application->signedIn(), $sheet->id, $name, $sheet->channel, $sheet->tags, self::REVISED_MESSAGE, $sheet->subject, $this->application->now());
        $this->application->dispatch($this->revision);
    }

    #[When('j\'y ajoute les contacts tagués :tags')]
    public function iAddTheContactsTagged(string $tags): void
    {
        $this->application->dispatch(new AddRecipientsByTags($this->application->signedIn(), $this->currentId(), self::listed($tags), $this->application->now()));
    }

    #[When('j\'y ajoute :contact')]
    public function iAddTheContact(string $contact): void
    {
        $this->application->dispatch(new AddRecipient($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact), $this->application->now()));
    }

    #[When('j\'en retire :contact')]
    public function iRemoveTheRecipient(string $contact): void
    {
        $this->application->dispatch(new RemoveRecipient($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact), $this->application->now()));
    }

    #[When('je supprime la campagne :name')]
    public function iDiscardTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->iDiscardIt();
    }

    #[Then('elle est figée')]
    public function itIsFrozen(): void
    {
        Expect::same('frozen', $this->sheet()->status);
    }

    #[Then('ses destinataires sont :contact, à traiter')]
    public function itsRecipientsArePending(string $contact): void
    {
        Expect::same([$this->scenario->personIdOf($contact) => 'à traiter'], array_map(static fn (RecipientInSheet $recipient): string => self::RECIPIENT_STATUSES[$recipient->status], $this->sheet()->recipients));
    }

    #[Then('ses destinataires sont :contact')]
    #[Then('ses destinataires sont toujours :contact')]
    public function itsRecipientsAre(string $contact): void
    {
        Expect::same([$this->scenario->personIdOf($contact)], array_keys($this->sheet()->recipients));
    }

    #[Then('ses destinataires sont :one et :other')]
    public function itsRecipientsAreBoth(string $one, string $other): void
    {
        Expect::same([$this->scenario->personIdOf($one), $this->scenario->personIdOf($other)], array_keys($this->sheet()->recipients));
    }

    private function itsRecipientsAreInAnyOrder(string ...$contacts): void
    {
        $expected = array_map($this->scenario->personIdOf(...), $contacts);
        $recipients = array_map(strval(...), array_keys($this->sheet()->recipients));
        sort($expected);
        sort($recipients);

        Expect::same($expected, $recipients);
    }

    #[Then(':contact est un destinataire')]
    public function isARecipient(string $contact): void
    {
        Expect::that(array_key_exists($this->scenario->personIdOf($contact), $this->sheet()->recipients), sprintf('%s is no recipient', $contact));
    }

    #[When('je lis le message pour :contact')]
    public function iReadTheMessageFor(string $contact): void
    {
        $this->rendered = $this->application->renderCampaignMessage(new RenderCampaignMessage($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact)));
    }

    #[When('je liste les campagnes')]
    public function iListTheCampaigns(): void
    {
        [$this->listed, $this->targeted] = $this->application->listCampaigns(new ListCampaigns($this->application->signedIn())) ?? [[], []];
    }

    #[When('je consulte la campagne :name')]
    public function iConsultTheCampaign(string $name): void
    {
        $this->current = $name;
        $this->application->consultCampaign(new ConsultCampaign($this->application->signedIn(), $this->currentId()));
    }

    #[Then('la campagne :name est en brouillon')]
    public function theCampaignIsADraft(string $name): void
    {
        $this->current = $name;
        Expect::same('draft', $this->sheet()->status);
    }

    #[Then('elle cible :')]
    public function itTargets(TableNode $table): void
    {
        $targeted = array_map(static fn (PersonCard $contact): array => ['prénom' => (string) $contact->firstName, 'nom' => (string) $contact->lastName], $this->targetedContacts());

        Expect::same($table->getColumnsHash(), $targeted);
    }

    #[Then('la campagne :name cible :one et :other')]
    public function theCampaignTargetsBoth(string $name, string $one, string $other): void
    {
        $this->current = $name;
        $targeted = array_map(static fn (PersonCard $contact): string => trim(sprintf('%s %s', $contact->firstName, $contact->lastName)), $this->targetedContacts());
        sort($targeted);

        Expect::same([$one, $other], $targeted);
    }

    #[Then('/^"(?<contact>[^"]+)", sans [^,]+, n\'est pas ciblée$/')]
    public function isNotTargeted(string $contact): void
    {
        $absent = $this->scenario->personIdOf($contact);
        Expect::that(!array_any($this->targetedContacts(), static fn (PersonCard $targeted): bool => $targeted->id === $absent), sprintf('%s is targeted', $contact));
    }

    #[Then('ses tags sont :tags')]
    public function itsTagsAre(string $tags): void
    {
        Expect::same($tags, implode(' ', $this->sheet()->targetedTags));
    }

    #[Then('ses tags comprennent :tag')]
    public function itsTagsInclude(string $tag): void
    {
        Expect::that(in_array($tag, $this->sheet()->targetedTags, true), sprintf('%s is not among %s', $tag, implode(' ', $this->sheet()->targetedTags)));
    }

    #[Then('la campagne :name est enregistrée ainsi')]
    public function theCampaignIsSavedSo(string $name): void
    {
        $this->current = $name;
        $sheet = $this->sheet();
        $revision = $this->revision ?? throw new \LogicException('No revision in the scenario');

        Expect::same(
            [$revision->name, $revision->channel, $revision->tags, $revision->message],
            [$sheet->name, $sheet->channel, $sheet->tags, $sheet->message],
        );
    }

    #[Then('il vaut :text')]
    public function itReads(string $text): void
    {
        Expect::same($text, $this->renderedMessage()->text);
    }

    #[Then('son objet vaut :subject')]
    public function itsSubjectReads(string $subject): void
    {
        Expect::same($subject, $this->renderedMessage()->subject);
    }

    #[Then('je n\'ai plus de campagne')]
    public function iHaveNoCampaignAnyMore(): void
    {
        $this->iListTheCampaigns();
        Expect::same([], $this->listed);
    }

    #[Then('j\'y vois :name, préparée par lui')]
    public function iSeeItPreparedByThem(string $name): void
    {
        $listed = array_find($this->listed, static fn (CampaignSheet $campaign): bool => $campaign->name === $name);
        Expect::that(null !== $listed, sprintf('%s is not listed', $name));
        Expect::same($this->scenario->memberIdOf('un autre prospecteur')->value, $listed?->authorId);
    }

    #[Then('les campagnes listées sont :')]
    public function theListedCampaignsAre(TableNode $table): void
    {
        $listed = array_map(fn (CampaignSheet $campaign): array => [
            'nom' => $campaign->name,
            'canal' => (string) array_search($campaign->channel, self::CHANNELS, true),
            'statut' => self::STATUSES[$campaign->status],
            'contactés' => (string) ($campaign->processed + $campaign->dismissed),
            'ciblés' => (string) ($this->targeted[$campaign->id] ?? 0),
        ], $this->listed);

        Expect::same($table->getColumnsHash(), $listed);
    }

    private function noteForTheRecipient(string $contact, string $name, \DateTimeImmutable $occurredAt, ?string $text, bool $missed): void
    {
        $this->current = $name;
        $id = $this->application->newIdentity();
        $this->application->dispatch(new NoteCampaignExchange(
            $this->application->signedIn(),
            $id,
            $this->currentId(),
            $this->scenario->personIdOf($contact),
            $occurredAt,
            $text,
            $missed,
            $this->application->now(),
        ));
        $this->noted = null === $this->application->refusal() ? $id : null;
        $this->notedWith = $contact;
    }

    private function dismiss(string $contact, string $name, ?string $reason): void
    {
        $this->current = $name;
        $this->application->dispatch(new DismissRecipient($this->application->signedIn(), $this->currentId(), $this->scenario->personIdOf($contact), $reason, $this->application->now()));
    }

    /**
     * @param array{status: string, missed: bool, reason: ?string} $expected
     */
    private function expectTheRecipient(string $contact, string $name, array $expected): void
    {
        $this->current = $name;
        $recipient = $this->recipient($contact);

        Expect::same($expected, ['status' => self::RECIPIENT_STATUSES[$recipient->status], 'missed' => $recipient->missed, 'reason' => $recipient->reason]);
    }

    private static function described(RecipientInSheet $recipient): string
    {
        return self::RECIPIENT_STATUSES[$recipient->status].($recipient->missed ? ', appel manqué' : '');
    }

    private function recipient(string $contact): RecipientInSheet
    {
        return $this->sheet()->recipients[$this->scenario->personIdOf($contact)] ?? throw new \LogicException(sprintf('%s is no recipient', $contact));
    }

    private function notedId(): string
    {
        return $this->noted ?? throw new \LogicException(sprintf('No exchange noted: %s', $this->application->refusal()?->getMessage()));
    }

    private function notedIdWith(string $contact): string
    {
        return $contact === $this->notedWith ? $this->notedId() : throw new \LogicException(sprintf('No exchange noted with %s in the campaign', $contact));
    }

    private function notedExchange(): ExchangeInTimeline
    {
        $id = $this->notedId();
        $timeline = $this->application->consultTimeline(new ConsultTimeline($this->application->signedIn(), $this->scenario->personIdOf($this->notedWith ?? ''), $this->application->now()));

        return array_find($timeline->exchanges ?? [], static fn (ExchangeInTimeline $exchange): bool => $exchange->id === $id) ?? throw new \LogicException('The timeline does not show the exchange noted');
    }

    private function prepare(string $name, string $channel, string $tags, string $message, ?string $subject = null): void
    {
        $this->current = $name;
        $this->campaignIds[$name] = $this->application->newIdentity();
        $this->application->dispatch(new PrepareCampaign(
            $this->application->signedIn(),
            $this->campaignIds[$name],
            $name,
            self::CHANNELS[$channel] ?? $channel,
            self::listed($tags),
            $message,
            $subject,
            $this->application->now(),
        ));
    }

    private function arrange(string $name, string $channel, string $tags, string $message, ?string $subject = null, ?AccountId $accountId = null, ?MemberId $author = null): void
    {
        $this->current = $name;
        $this->campaignIds[$name] = $this->application->newIdentity();
        $channel = Channel::named(self::CHANNELS[$channel]);
        $this->application->campaigns->save(Campaign::prepare(
            new CampaignId($this->campaignIds[$name]),
            $accountId ?? $this->scenario->me()->accountId(),
            CampaignName::of($name),
            Targeting::by($channel, self::listed($tags)),
            MessageTemplate::of($message, $subject, $channel),
            by: $author ?? new MemberId($this->application->signedIn()),
            at: $this->application->now(),
        ));
    }

    /**
     * As the screen of a draft composes it: its targeted tags searched as every search by tags, the contacts read
     * by PersonalData.
     *
     * @return list<PersonCard>
     */
    private function targetedContacts(): array
    {
        $page = $this->application->searchContactsByTags(new SearchContactsByTags($this->application->signedIn(), $this->sheet()->targetedTags)) ?? throw new \LogicException(sprintf('The search was refused: %s', $this->application->refusal()?->getMessage()));
        $read = $this->application->consultPersons(new ConsultPersons($this->application->signedIn(), $page->contactIds)) ?? [];

        return array_values(array_filter(array_map(static fn (string $id): ?PersonCard => $read[$id] ?? null, $page->contactIds)));
    }

    private function sheet(): CampaignSheet
    {
        return $this->application->consultCampaign(new ConsultCampaign($this->application->signedIn(), $this->currentId()))->campaign
            ?? throw new \LogicException(sprintf('The campaign was refused: %s', $this->application->refusal()?->getMessage()));
    }

    private function renderedMessage(): CampaignMessage
    {
        return $this->rendered ?? throw new \LogicException(sprintf('The message was refused: %s', $this->application->refusal()?->getMessage()));
    }

    private function currentName(): string
    {
        return $this->current ?? throw new \LogicException('No campaign in the scenario');
    }

    private function currentId(): string
    {
        return $this->campaignIds[$this->currentName()] ?? throw new \LogicException(sprintf('No campaign "%s" in the scenario', $this->currentName()));
    }

    /**
     * @return list<string> the tags of `#ESN #STRASBOURG`, none for nothing
     */
    private static function listed(string $tags): array
    {
        return array_values(array_filter(explode(' ', $tags), static fn (string $tag): bool => '' !== $tag));
    }
}
