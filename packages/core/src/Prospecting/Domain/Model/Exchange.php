<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCancelled;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMadeConform;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedDone;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedMissed;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangePlanned;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeTextCorrected;
use Prospecter\Core\Prospecting\Domain\Event\FileAttached;
use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\NoRoomForAnAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\NothingToMakeConform;
use Prospecter\Core\Prospecting\Domain\Exception\NotTheHolder;
use Prospecter\Core\Prospecting\Domain\Exception\NotUpcoming;
use Prospecter\Core\Prospecting\Domain\Exception\NotYetDue;
use Prospecter\Core\Prospecting\Domain\Exception\PlannedExchangeInThePast;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownAttachment;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a fact of the real world between a prospector of the account and a contact, or the
 * organization itself, a call, a message or a meeting; not a change made in the application (ADR 0074, 0076).
 * Planned, it is still to come, held by the member who planned it, its author, who alone marks it done or missed
 * once its time has come, moves it or cancels it (ADR 0111, 0112).
 * Its text may hold personal data: it is kept for the organizations the contact was part of when it was noted, or
 * for the organization itself, and lives as long as one of them is prospected.
 * Its contact forgotten, it stays, to make conform (ADR 0079).
 */
final class Exchange implements ReleasesEvents
{
    use RecordsEvents;

    public const int MOST_ATTACHMENTS = 10;

    private \DateTimeImmutable $modifiedAt;
    private ?Correction $lastCorrection = null;
    private bool $contactForgotten = false;
    private ?Correction $madeConform = null;
    private ?Cancellation $cancellation = null;
    /** @var list<Attachment> */
    private array $attachments = [];

    /**
     * @param list<OrganizationId> $keptFor
     */
    private function __construct(
        private readonly ExchangeId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private readonly ?PersonId $contact,
        private array $keptFor,
        private ExchangeFacts $facts,
        private ExchangeStatus $status,
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    /**
     * Done, or missed when the contact did not answer or come.
     *
     * @param list<OrganizationId> $organizationsOfTheContact
     *
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public static function notedWithAContact(AccountId $accountId, MemberId $by, ExchangeId $id, PersonId $contact, array $organizationsOfTheContact, ExchangeFacts $facts, ExchangeStatus $outcome, \DateTimeImmutable $at): self
    {
        return self::noted($accountId, $by, $id, $contact, $organizationsOfTheContact, $facts, $outcome, $at);
    }

    /**
     * With the organization itself (its switchboard, its generic email): kept for it alone, in the timeline
     * of no contact.
     *
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public static function notedWithTheOrganization(Organization $organization, MemberId $by, ExchangeId $id, ExchangeFacts $facts, ExchangeStatus $outcome, \DateTimeImmutable $at): self
    {
        return self::noted($organization->accountId(), $by, $id, null, [$organization->id()], $facts, $outcome, $at);
    }

    /**
     * @param list<OrganizationId> $keptFor
     */
    private static function noted(AccountId $accountId, MemberId $by, ExchangeId $id, ?PersonId $contact, array $keptFor, ExchangeFacts $facts, ExchangeStatus $outcome, \DateTimeImmutable $at): self
    {
        self::ensureHappenedBefore($facts, $at);
        self::ensureMissable($facts->channel, $outcome);
        $exchange = new self($id, $accountId, $by, $contact, $keptFor, $facts, $outcome, $at);
        $exchange->recordThat(new ExchangeNoted($accountId, $by, $id, $at));

        return $exchange;
    }

    /**
     * Kept, like an exchange noted, for the organizations the contact is part of now.
     *
     * @param list<OrganizationId> $organizationsOfTheContact
     *
     * @throws PlannedExchangeInThePast
     */
    public static function plannedWithAContact(AccountId $accountId, MemberId $by, ExchangeId $id, PersonId $contact, array $organizationsOfTheContact, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        return self::planned($accountId, $by, $id, $contact, $organizationsOfTheContact, $facts, $at);
    }

    /**
     * @throws PlannedExchangeInThePast
     */
    public static function plannedWithTheOrganization(Organization $organization, MemberId $by, ExchangeId $id, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        return self::planned($organization->accountId(), $by, $id, null, [$organization->id()], $facts, $at);
    }

    /**
     * @param list<OrganizationId> $keptFor
     */
    private static function planned(AccountId $accountId, MemberId $by, ExchangeId $id, ?PersonId $contact, array $keptFor, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        self::ensureToCome($facts->occurredAt, $at);
        $exchange = new self($id, $accountId, $by, $contact, $keptFor, $facts, ExchangeStatus::Planned, $at);
        $exchange->recordThat(new ExchangePlanned($accountId, $by, $id, $at));

        return $exchange;
    }

    /**
     * @throws ExchangeInTheFuture
     */
    private static function ensureHappenedBefore(ExchangeFacts $facts, \DateTimeImmutable $at): void
    {
        if ($facts->occurredAt > $at) {
            throw ExchangeInTheFuture::because('An exchange is never in the future', 'occurredAt');
        }
    }

    /**
     * @throws ChannelNotMissed
     */
    private static function ensureMissable(Channel $channel, ExchangeStatus $outcome): void
    {
        if (ExchangeStatus::Missed === $outcome && !$channel->mayBePlanned()) {
            throw ChannelNotMissed::because('Only a call, a meeting or a video call may be missed', 'missed', context: ['channel' => $channel->value]);
        }
    }

    /**
     * @throws PlannedExchangeInThePast
     */
    private static function ensureToCome(\DateTimeImmutable $plannedAt, \DateTimeImmutable $at): void
    {
        if ($plannedAt <= $at) {
            throw PlannedExchangeInThePast::because('A planned exchange is in the future', 'occurredAt');
        }
    }

    public function id(): ExchangeId
    {
        return $this->id;
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    /**
     * Its contact forgotten, it may still hold their personal data: in its text until it is declared conform,
     * in its files until they are removed.
     */
    public function isToMakeConform(): bool
    {
        return ExchangeConformity::isToMakeConform($this->contactForgotten, null !== $this->madeConform, [] !== $this->attachments);
    }

    private function awaitsBeingMadeConform(): bool
    {
        return ExchangeConformity::awaitsBeingMadeConform($this->contactForgotten, null !== $this->madeConform);
    }

    /**
     * The files joined to it, in the order they were.
     *
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return $this->attachments;
    }

    /**
     * The files joined to it a prospector reads: none without the right to manage personal data (ADR 0078).
     *
     * @return list<Attachment>
     */
    public function attachmentsReadBy(Prospector $prospector): array
    {
        return ExchangeReading::readsTheAttachments($prospector) ? $this->attachments : [];
    }

    /**
     * A file joined to it, hidden to a prospector without the right to manage personal data (ADR 0078).
     *
     * @throws PersonalDataManagersOnly|UnknownAttachment
     */
    public function attachmentReadBy(Prospector $prospector, AttachmentId $id): Attachment
    {
        $prospector->ensureMayExchangeWithTheContacts();

        return $this->attachment($id);
    }

    /**
     * @throws UnknownAttachment
     */
    private function attachment(AttachmentId $id): Attachment
    {
        return array_find($this->attachments, static fn (Attachment $attachment): bool => $attachment->id->equals($id)) ?? throw UnknownAttachment::identifiedBy($id);
    }

    public function isTextFlaggedFor(Prospector $prospector): bool
    {
        return ExchangeReading::isTextFlagged(null !== $this->facts->text, $prospector);
    }

    /**
     * Once it took place, done or missed. The contact stays: an exchange with somebody else is erased, then noted
     * again.
     *
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public function correct(ExchangeFacts $facts, ExchangeStatus $outcome, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectExchange, $by, $at);
        self::ensureHappenedBefore($facts, $at);
        self::ensureMissable($facts->channel, $outcome);
        $this->facts = $facts;
        $this->status = $outcome;
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeCorrected($this->accountId, $by, $this->id, $at));
    }

    /**
     * To make it conform, without the right to manage personal data too: its other facts stay.
     */
    public function correctText(?string $text, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectText, $by, $at);
        $this->facts = $this->facts->withText($text);
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeTextCorrected($this->accountId, $by, $this->id, $at));
    }

    /**
     * Once its text retouched: its files, if any, are made conform by removing them.
     *
     * @throws NothingToMakeConform
     */
    public function declareConform(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MakeConform, $by, $at);
        $this->modifiedAt = $at;
        $this->madeConform = new Correction($by, $at);
        $this->recordThat(new ExchangeMadeConform($this->accountId, $by, $this->id, $at));
    }

    private function correctedBy(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->modifiedAt = $at;
        $this->lastCorrection = new Correction($by, $at);
    }

    /**
     * Its time come, it took place: its report replaces its agenda, its moment the one it really took place.
     *
     * @throws NotUpcoming|NotTheHolder|NotYetDue|ExchangeInTheFuture
     */
    public function markDone(\DateTimeImmutable $occurredAt, ?string $report, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MarkExchangeDone, $by, $at);
        $facts = $this->facts->happened($occurredAt, $report);
        self::ensureHappenedBefore($facts, $at);
        $this->facts = $facts;
        $this->changeStatus(ExchangeStatus::Done, $at);
        $this->recordThat(new ExchangeMarkedDone($this->accountId, $by, $this->id, $at));
    }

    /**
     * Its time come, the contact did not come or answer.
     *
     * @throws NotUpcoming|NotTheHolder|NotYetDue
     */
    public function markMissed(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MarkExchangeMissed, $by, $at);
        $this->changeStatus(ExchangeStatus::Missed, $at);
        $this->recordThat(new ExchangeMarkedMissed($this->accountId, $by, $this->id, $at));
    }

    /**
     * To another moment to come, its duration kept.
     *
     * @throws NotUpcoming|NotTheHolder|PlannedExchangeInThePast
     */
    public function moveTo(\DateTimeImmutable $plannedAt, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MoveExchange, $by, $at);
        self::ensureToCome($plannedAt, $at);
        $this->facts = $this->facts->movedTo($plannedAt);
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeMoved($this->accountId, $by, $this->id, $at));
    }

    /**
     * It stays in the timelines, cancelled; who called it off, the prospector or the contact, when it is said.
     *
     * @throws NotUpcoming|NotTheHolder
     */
    public function cancel(?Canceller $canceller, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CancelExchange, $by, $at);
        $this->cancelledBy(new Cancellation($by, $at, $canceller));
    }

    private function cancelledBy(Cancellation $cancellation): void
    {
        $this->cancellation = $cancellation;
        $this->changeStatus(ExchangeStatus::Cancelled, $cancellation->at);
        $this->recordThat(new ExchangeCancelled($this->accountId, $cancellation->by, $this->id, $cancellation->at));
    }

    private function changeStatus(ExchangeStatus $status, \DateTimeImmutable $at): void
    {
        $this->status = $status;
        $this->modifiedAt = $at;
    }

    /**
     * Erased from every timeline, once the repository removed it.
     */
    public function erase(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::EraseExchange, $by, $at);
        $this->recordThat(new ExchangeErased($this->accountId, $by, $this->id, array_map(static fn (Attachment $attachment): string => $attachment->file, $this->attachments), $at));
    }

    /**
     * A file it brought, or that was sent with it.
     *
     * @throws NoRoomForAnAttachment
     */
    public function attach(Attachment $attachment, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::AttachFile, $by, $at);
        $this->attachments[] = $attachment;
        $this->modifiedAt = $at;
        $this->recordThat(new FileAttached($this->accountId, $by, $this->id, $attachment->id, $at));
    }

    /**
     * @throws UnknownAttachment
     */
    public function removeAttachment(AttachmentId $id, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::RemoveAttachment, $by, $at);
        $removed = $this->attachment($id);
        $this->attachments = array_values(array_filter($this->attachments, static fn (Attachment $attachment): bool => $attachment !== $removed));
        $this->modifiedAt = $at;
        $this->recordThat(new AttachmentRemoved($this->accountId, $by, $this->id, $id, $removed->file, $at));
    }

    /**
     * The account stopped prospecting this organization: kept for no organization any more, the exchange must
     * not stay (ExchangeLeftWithoutOrganization).
     */
    public function stopKeepingFor(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->keptFor = array_values(array_filter($this->keptFor, static fn (OrganizationId $keptFor): bool => !$keptFor->equals($organization)));
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeNoLongerKeptFor($this->accountId, $by, $this->id, $organization, $at));

        if (!$this->isKeptForAnOrganization()) {
            $this->recordThat(new ExchangeLeftWithoutOrganization($this->accountId, $by, $this->id, $at));
        }
    }

    /**
     * Its contact erased by PersonalData: the exchange stays in the timelines of its organizations, its text
     * and its files to make conform (ADR 0079); still to come, it is cancelled, whoever holds it.
     */
    public function forgetItsContact(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->contactForgotten = true;
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeContactForgotten($this->accountId, $by, $this->id, $at));

        if ($this->isToCome($at)) {
            $this->cancelledBy(new Cancellation($by, $at, null));
        }
    }

    private function isToCome(\DateTimeImmutable $at): bool
    {
        return ExchangeStatus::Planned === $this->status && $this->facts->occurredAt > $at;
    }

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }

    /**
     * Every intention starts with it: what the exchange does not offer the member now is refused.
     *
     * @throws NoRoomForAnAttachment|NothingToMakeConform|NotPossibleInItsState|NotTheHolder
     */
    private function ensurePossible(ExchangeIntent $intent, MemberId $by, \DateTimeImmutable $at): void
    {
        $situation = new ExchangeSituation(count($this->attachments), $this->awaitsBeingMadeConform(), $this->status, $this->facts->occurredAt <= $at, $this->createdBy->equals($by));

        if (in_array($intent, ExchangePossibilities::given($situation), true)) {
            return;
        }

        throw match ($intent) {
            ExchangeIntent::AttachFile => NoRoomForAnAttachment::because(sprintf('An exchange holds %d attachments at most', self::MOST_ATTACHMENTS)),
            ExchangeIntent::MakeConform => NothingToMakeConform::because('Only an exchange with a forgotten contact is to make conform'),
            ExchangeIntent::MarkExchangeDone, ExchangeIntent::MarkExchangeMissed, ExchangeIntent::MoveExchange, ExchangeIntent::CancelExchange => $this->whyNotHandledBy($by),
            ExchangeIntent::CorrectExchange => NotPossibleInItsState::because('Only an exchange that took place is corrected'),
            default => NotPossibleInItsState::because(sprintf('%s is not possible now', $intent->name)),
        };
    }

    /**
     * No longer to come, held by another member, or its time not come.
     */
    private function whyNotHandledBy(MemberId $by): NotUpcoming|NotTheHolder|NotYetDue
    {
        return match (true) {
            ExchangeStatus::Planned !== $this->status => NotUpcoming::because('Only an upcoming exchange may change'),
            !$this->createdBy->equals($by) => NotTheHolder::because('Only its holder handles a planned exchange'),
            default => NotYetDue::because('A planned exchange is done or missed once its time has come'),
        };
    }
}
