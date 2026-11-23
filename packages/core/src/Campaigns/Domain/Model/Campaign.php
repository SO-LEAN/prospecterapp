<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Model;

use Prospecter\Core\Campaigns\Domain\Event\CampaignDiscarded;
use Prospecter\Core\Campaigns\Domain\Event\CampaignFrozen;
use Prospecter\Core\Campaigns\Domain\Event\CampaignPrepared;
use Prospecter\Core\Campaigns\Domain\Event\CampaignRecipientsChanged;
use Prospecter\Core\Campaigns\Domain\Event\CampaignRevised;
use Prospecter\Core\Campaigns\Domain\Event\CampaignStarted;
use Prospecter\Core\Campaigns\Domain\Event\CampaignStopped;
use Prospecter\Core\Campaigns\Domain\Event\DailyCapSet;
use Prospecter\Core\Campaigns\Domain\Event\RecipientDismissed;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignNotFrozen;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignNotRunning;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\FrozenSearch;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTagSelection;
use Prospecter\Core\Campaigns\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Campaigns\Domain\Exception\StartedCampaign;
use Prospecter\Core\Campaigns\Domain\Exception\StartedCampaignNotDiscarded;
use Prospecter\Core\Campaigns\Domain\Exception\TooManyRecipients;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\UnreachableContact;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignProgress;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a communication campaign that an account's team prepares (ADR 0144). The members contact each person
 * it targets themselves, through its channel. The campaign keeps its message to remind them. Prospecter sends nothing.
 * The whole team shares the campaign, and its author prepares it.
 *
 * A draft targets the contacts that carry its tags now. A frozen campaign keeps its recipients, and only a member
 * changes them by hand. A started campaign runs as it was frozen, one recipient after another. The exchange logged for
 * a recipient processes them, or a member dismisses them. The campaign ends once nobody is left to handle, unless a
 * member stops it first. A started campaign is never discarded (ADR 0145).
 */
final class Campaign implements ReleasesEvents
{
    use RecordsEvents;

    private CampaignStatus $status = CampaignStatus::Draft;
    private Recipients $recipients;
    private \DateTimeImmutable $modifiedAt;
    private ?\DateTimeImmutable $startedAt = null;
    private ?\DateTimeImmutable $stoppedAt = null;
    private ?\DateTimeImmutable $endedAt = null;
    private ?DailyCap $dailyCap = null;

    private function __construct(
        private readonly CampaignId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private CampaignName $name,
        private Targeting $targeting,
        private MessageTemplate $message,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->recipients = Recipients::none();
        $this->modifiedAt = $createdAt;
    }

    public static function prepare(Gesture $gesture, CampaignId $id, CampaignBrief $brief, ?DailyCap $dailyCap = null): self
    {
        $campaign = new self($id, $gesture->accountId, $gesture->by, $brief->name, $brief->targeting, $brief->message, $gesture->at);
        $campaign->dailyCap = $dailyCap;
        $campaign->recordThat(new CampaignPrepared($gesture->accountId, $gesture->by, $id, $gesture->at));

        return $campaign;
    }

    public function id(): CampaignId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    /**
     * Its channel and tags no longer change once it is frozen.
     *
     * @throws NotPossibleInItsState
     * @throws FrozenSearch
     */
    public function revise(CampaignBrief $brief, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::ReviseCampaign);

        if (!$this->status->followsTheTags() && !$this->targeting->searchesAs($brief->targeting)) {
            throw FrozenSearch::unchanged();
        }

        $this->name = $brief->name;
        $this->targeting = $brief->targeting;
        $this->message = $brief->message;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignRevised($this->accountId, $by, $this->id, $at));
    }

    /**
     * The campaign is discarded everywhere once the repository removes it.
     *
     * @throws NotPossibleInItsState
     */
    public function discard(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::DiscardCampaign);
        $this->recordThat(new CampaignDiscarded($this->accountId, $by, $this->id, $at));
    }

    /**
     * Keeps the contacts its search finds now as its recipients, for good.
     *
     * @param array<string, ContactTags> $contacts every contact of the account, by identity
     *
     * @throws NotPossibleInItsState
     * @throws CampaignWithoutRecipient
     * @throws TooManyRecipients
     */
    public function freeze(array $contacts, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::FreezeCampaign);
        $this->recipients = Recipients::frozen(self::found($this->targeting, $contacts));
        $this->status = CampaignStatus::Frozen;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignFrozen($this->accountId, $by, $this->id, $at));
    }

    /**
     * Runs the campaign as it was frozen. Nothing in it changes any more. A frozen campaign that lost all its contacts
     * to erasure is cancelled in effect: it no longer starts.
     *
     * @throws CampaignNotFrozen
     * @throws CampaignWithoutRecipient
     */
    public function start(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::StartCampaign);
        $this->status = CampaignStatus::Started;
        $this->startedAt = $at;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignStarted($this->accountId, $by, $this->id, $at));
    }

    /**
     * Stops running the campaign. The campaign stays for the team to read.
     *
     * @throws NotPossibleInItsState
     */
    public function stop(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::StopCampaign);
        $this->status = CampaignStatus::Stopped;
        $this->stoppedAt = $at;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignStopped($this->accountId, $by, $this->id, $at));
    }

    /**
     * Processes a recipient when Prospecting reports that an exchange was logged for them in the campaign. This is a
     * system reaction, and it records nothing. A recipient already handled, or a campaign no longer running, stays as
     * it is.
     */
    public function process(PersonId $contact, string $exchangeId, RecipientOutcome $outcome, \DateTimeImmutable $at): void
    {
        if (!$this->status->isRunning()) {
            return;
        }

        $this->recipients = $this->recipients->processing($contact, $exchangeId, $outcome, $at);
        $this->endOnceNobodyIsPending($at);
    }

    /**
     * Puts a recipient back to handle when Prospecting reports that the exchange that processed them was erased (ADR
     * 0152). An ended campaign runs again. A stopped one stays stopped. This is a system reaction, and it records
     * nothing. An exchange that processed nobody changes nothing.
     */
    public function reopenAfterErasureOf(string $exchangeId, \DateTimeImmutable $at): void
    {
        if (!$this->recipients->processedBy($exchangeId)) {
            return;
        }

        $this->recipients = $this->recipients->reopening($exchangeId);
        $this->modifiedAt = $at;

        if (CampaignStatus::Ended === $this->status) {
            $this->status = CampaignStatus::Started;
            $this->endedAt = null;
        }
    }

    /**
     * Keeps a recipient processed when the exchange that processed them went with its organization, and no member
     * erased it. The recipient no longer links to the exchange (ADR 0155). This is a system reaction, and it records
     * nothing.
     */
    public function forgetExchangeGoneWithItsOrganization(string $exchangeId): void
    {
        $this->recipients = $this->recipients->forgettingExchange($exchangeId);
    }

    /**
     * Its cap changes at any time until the campaign is stopped or ended, even once it has started. A null cap takes
     * the cap off.
     *
     * @throws NotPossibleInItsState
     */
    public function setDailyCap(?DailyCap $dailyCap, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::SetDailyCap);
        $this->dailyCap = $dailyCap;
        $this->modifiedAt = $at;
        $this->recordThat(new DailyCapSet($this->accountId, $by, $this->id, $at));
    }

    /**
     * Dismisses the recipient without an exchange, for a reason when the member gives one.
     *
     * @throws CampaignNotRunning
     * @throws UnknownRecipient
     * @throws RecipientAlreadyProcessed
     */
    public function dismiss(PersonId $contact, ?string $reason, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::RunCampaign);
        $this->recipients = $this->recipients->dismissing($contact, $reason);
        $this->modifiedAt = $at;
        $this->recordThat(new RecipientDismissed($this->accountId, $by, $this->id, $contact, $at));
        $this->endOnceNobodyIsPending($at);
    }

    /**
     * Adds the contacts that carry the typed tags and the channel's tag. A contact who is already a recipient is kept
     * once.
     *
     * @param list<string>               $typed
     * @param array<string, ContactTags> $contacts every contact of the account, by identity
     *
     * @throws NotPossibleInItsState
     * @throws InvalidTag
     * @throws InvalidTagSelection
     * @throws TooManyRecipients
     */
    public function addRecipientsCarrying(array $typed, array $contacts, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::AddRecipients);
        $this->changeRecipients($this->recipients->with(self::found($this->targeting->searching($typed), $contacts)), $by, $at);
    }

    /**
     * @throws NotPossibleInItsState
     * @throws UnreachableContact    when the contact lacks the means its channel needs
     * @throws TooManyRecipients
     */
    public function addRecipient(Candidate $candidate, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::AddRecipients);

        if (!$this->targeting->reaches($candidate->tags)) {
            throw UnreachableContact::by($candidate->name(), $this->targeting->channel);
        }

        $this->changeRecipients($this->recipients->with([$candidate->contact]), $by, $at);
    }

    /**
     * @throws NotPossibleInItsState
     * @throws UnknownRecipient
     * @throws CampaignWithoutRecipient
     */
    public function removeRecipient(PersonId $contact, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::RemoveRecipient);
        $this->changeRecipients($this->recipients->without($contact), $by, $at);
    }

    /**
     * A contact that PersonalData forgot leaves the recipients, even when it is the last one. A started campaign keeps
     * the contact, without its name and no longer to handle. A running campaign ends once nobody is left to handle.
     */
    public function forget(PersonId $contact, \DateTimeImmutable $at): void
    {
        $this->recipients = $this->status->hasStarted() ? $this->recipients->erasing($contact) : $this->recipients->forgetting($contact);

        if ($this->status->isRunning()) {
            $this->endOnceNobodyIsPending($at);
        }
    }

    #[\NoDiscard]
    public function hasRecipient(PersonId $contact): bool
    {
        return $this->recipients->includes($contact);
    }

    /**
     * The first recipient still to handle while the campaign runs. It skips the recipients that Prospecting logged an
     * exchange of the campaign with, but that the campaign has not processed yet (ADR 0195). When nobody else is left,
     * the campaign is about to end.
     *
     * @param list<PersonId> $exchangedWith
     *
     * @throws CampaignNotRunning
     */
    #[\NoDiscard]
    public function nextRecipient(array $exchangedWith): PersonId
    {
        $this->ensurePossible(CampaignIntent::RunCampaign);

        return $this->recipients->firstPending($exchangedWith) ?? throw CampaignNotRunning::toRun();
    }

    /**
     * @return list<PersonId>
     */
    #[\NoDiscard]
    public function pendingRecipients(): array
    {
        return $this->recipients->pending();
    }

    #[\NoDiscard]
    public function channel(): Channel
    {
        return $this->targeting->channel;
    }

    #[\NoDiscard]
    public function messageMode(): MessageMode
    {
        return $this->message->mode;
    }

    /**
     * @param list<PersonId> $exchangedWith
     */
    #[\NoDiscard]
    public function stillToHandle(array $exchangedWith): int
    {
        return $this->recipients->pendingCount($exchangedWith);
    }

    #[\NoDiscard]
    public function dailyCap(): ?DailyCap
    {
        return $this->dailyCap;
    }

    #[\NoDiscard]
    public function processedOnTheDayOf(\DateTimeImmutable $at, \DateTimeZone $clock): int
    {
        return CampaignProgress::processedOnTheDayOf($at, $clock, $this->recipients->processedAt());
    }

    #[\NoDiscard]
    public function messageFor(Addressee $addressee): RenderedMessage
    {
        return $this->message->renderedFor($addressee);
    }

    private function endOnceNobodyIsPending(\DateTimeImmutable $at): void
    {
        if ($this->recipients->nonePending()) {
            $this->status = CampaignStatus::Ended;
            $this->endedAt = $at;
            $this->modifiedAt = $at;
        }
    }

    private function changeRecipients(Recipients $recipients, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->recipients = $recipients;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignRecipientsChanged($this->accountId, $by, $this->id, $at));
    }

    /**
     * @param array<string, ContactTags> $contacts
     *
     * @return list<PersonId>
     */
    private static function found(Targeting $targeting, array $contacts): array
    {
        $found = array_filter($contacts, $targeting->targets(...));

        return array_map(static fn (int|string $id): PersonId => new PersonId((string) $id), array_keys($found));
    }

    private function ensurePossible(CampaignIntent $intent): void
    {
        if (!in_array($intent, CampaignPossibilities::given($this->status, $this->recipients->keepsSomeone()), true)) {
            throw $this->refusalOf($intent);
        }
    }

    /**
     * A started campaign tells why it no longer changes. A frozen one tells why it does not start. Any other state
     * refuses what it does not offer.
     */
    private function refusalOf(CampaignIntent $intent): BusinessRuleViolation
    {
        return match (true) {
            CampaignIntent::StartCampaign === $intent && CampaignStatus::Frozen === $this->status => CampaignWithoutRecipient::left(),
            CampaignIntent::StartCampaign === $intent => CampaignNotFrozen::toStart(),
            CampaignIntent::RunCampaign === $intent => CampaignNotRunning::toRun(),
            CampaignIntent::DiscardCampaign === $intent && $this->status->hasStarted() => StartedCampaignNotDiscarded::stoppedInstead(),
            CampaignIntent::StopCampaign !== $intent && $this->status->hasStarted() => StartedCampaign::unchanged(),
            default => NotPossibleInItsState::because(sprintf('A %s campaign does not offer it', $this->status->value)),
        };
    }
}
