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
use Prospecter\Core\Campaigns\Domain\Exception\CampaignNotFrozen;
use Prospecter\Core\Campaigns\Domain\Exception\CampaignWithoutRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\FrozenSearch;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTag;
use Prospecter\Core\Campaigns\Domain\Exception\InvalidTagSelection;
use Prospecter\Core\Campaigns\Domain\Exception\StartedCampaign;
use Prospecter\Core\Campaigns\Domain\Exception\StartedCampaignNotDiscarded;
use Prospecter\Core\Campaigns\Domain\Exception\TooManyRecipients;
use Prospecter\Core\Campaigns\Domain\Exception\UnknownRecipient;
use Prospecter\Core\Campaigns\Domain\Exception\UnreachableContact;
use Prospecter\Core\Campaigns\Domain\Policy\CampaignPossibilities;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a campaign of communication the team of an account prepares (ADR 0144), its members contacting each
 * person it targets themselves, by its channel, the message it keeps reminding them; Prospecter sends nothing. Shared
 * by the team, prepared by its author. A draft targets the contacts carrying its tags as they are now; frozen, it keeps
 * its recipients, changed by hand only; started, it is led as it was frozen, then stopped, never discarded.
 */
final class Campaign implements ReleasesEvents
{
    use RecordsEvents;

    private CampaignStatus $status = CampaignStatus::Draft;
    private Recipients $recipients;
    private \DateTimeImmutable $modifiedAt;
    private ?\DateTimeImmutable $startedAt = null;
    private ?\DateTimeImmutable $stoppedAt = null;

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

    public static function prepare(CampaignId $id, AccountId $accountId, CampaignName $name, Targeting $targeting, MessageTemplate $message, MemberId $by, \DateTimeImmutable $at): self
    {
        $campaign = new self($id, $accountId, $by, $name, $targeting, $message, $at);
        $campaign->recordThat(new CampaignPrepared($accountId, $by, $id, $at));

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
     * Its channel and its tags no longer change once it is frozen.
     *
     * @throws NotPossibleInItsState
     * @throws FrozenSearch
     */
    public function revise(CampaignName $name, Targeting $targeting, MessageTemplate $message, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::ReviseCampaign);

        if (!$this->status->followsTheTags() && !$this->targeting->searchesAs($targeting)) {
            throw FrozenSearch::unchanged();
        }

        $this->name = $name;
        $this->targeting = $targeting;
        $this->message = $message;
        $this->modifiedAt = $at;
        $this->recordThat(new CampaignRevised($this->accountId, $by, $this->id, $at));
    }

    /**
     * Discarded everywhere, once the repository removed it.
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
     * Leads it as it was frozen: nothing of it changes any more.
     *
     * @throws CampaignNotFrozen
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
     * Stops leading it, kept for the team to read.
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
     * Adds the contacts carrying the tags typed and that of its channel, those already recipients kept once.
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
     * @throws UnreachableContact    when the contact has not the means of its channel
     * @throws TooManyRecipients
     */
    public function addRecipient(PersonId $contact, ContactTags $tags, Addressee $addressee, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(CampaignIntent::AddRecipients);

        if (!$this->targeting->reaches($tags)) {
            throw UnreachableContact::by($addressee->name(), $this->targeting->channel);
        }

        $this->changeRecipients($this->recipients->with([$contact]), $by, $at);
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
     * A contact PersonalData forgot leaves its recipients, even the last one; started, it stays, without its name, no
     * longer to handle.
     */
    public function forget(PersonId $contact): void
    {
        $this->recipients = $this->status->hasStarted() ? $this->recipients->erasing($contact) : $this->recipients->forgetting($contact);
    }

    #[\NoDiscard]
    public function hasRecipient(PersonId $contact): bool
    {
        return $this->recipients->includes($contact);
    }

    #[\NoDiscard]
    public function messageFor(Addressee $addressee): RenderedMessage
    {
        return $this->message->renderedFor($addressee);
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
        if (!in_array($intent, CampaignPossibilities::given($this->status), true)) {
            throw $this->refusalOf($intent);
        }
    }

    /**
     * Started, a campaign tells why it no longer changes; any other state refuses what it does not offer.
     */
    private function refusalOf(CampaignIntent $intent): NotPossibleInItsState
    {
        return match (true) {
            CampaignIntent::StartCampaign === $intent => CampaignNotFrozen::toStart(),
            CampaignIntent::DiscardCampaign === $intent && $this->status->hasStarted() => StartedCampaignNotDiscarded::stoppedInstead(),
            CampaignIntent::StopCampaign !== $intent && $this->status->hasStarted() => StartedCampaign::unchanged(),
            default => NotPossibleInItsState::because(sprintf('A %s campaign does not offer it', $this->status->value)),
        };
    }
}
