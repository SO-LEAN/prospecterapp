<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCancelled;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeLeftWithoutOrganization;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedDone;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMarkedMissed;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangePlanned;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeRedacted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeTextCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeVenueCorrected;
use Prospecter\Core\Prospecting\Domain\Event\FileAttached;
use Prospecter\Core\Prospecting\Domain\Event\VideoCallLinkReceived;
use Prospecter\Core\Prospecting\Domain\Exception\CampaignNotRunning;
use Prospecter\Core\Prospecting\Domain\Exception\ChannelNotMissed;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\LinkOnlyForAVideoCall;
use Prospecter\Core\Prospecting\Domain\Exception\NoRoomForAnAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\NothingToRedact;
use Prospecter\Core\Prospecting\Domain\Exception\NotTheHolder;
use Prospecter\Core\Prospecting\Domain\Exception\NotUpcoming;
use Prospecter\Core\Prospecting\Domain\Exception\NotYetDue;
use Prospecter\Core\Prospecting\Domain\Exception\PlaceOnlyForAMeeting;
use Prospecter\Core\Prospecting\Domain\Exception\PlannedExchangeInThePast;
use Prospecter\Core\Prospecting\Domain\Exception\RecipientAlreadyProcessed;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownRecipient;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
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
 * Its contact forgotten, it stays, to make conform (ADR 0079). Noted for a recipient of a campaign, it keeps the
 * campaign (ADR 0145).
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
    private ?CampaignId $campaign = null;
    private ?ImportedEmail $importedFrom = null;
    private ?ExchangeFingerprint $fingerprint = null;
    private ?Erasure $erasure = null;
    private bool $bookedByLink = false;
    private Origin $origin = Origin::Hand;
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
     * Done, or missed when the contact did not answer or come. It is kept for the organizations of its correspondent.
     * Noted for a pending recipient of a running campaign, it keeps the campaign (ADR 0145); imported from the member's
     * mailbox, it keeps the e-mail (ADR 0154). Its fingerprint comes from where it was read, when given (ADR 0166).
     * The member notes it by hand, or an AI notes it for them (ADR 0185, 0186).
     *
     * @throws CampaignNotRunning|UnknownRecipient|RecipientAlreadyProcessed
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public static function noted(Gesture $gesture, ExchangeId $id, Correspondent $correspondent, Noting $noting): self
    {
        $noting->ensureToHandle();
        self::ensureHappenedBefore($noting->facts, $gesture->at);
        self::ensureMissable($noting->facts->channel, $noting->outcome);
        $exchange = new self($id, $gesture->accountId, $gesture->by, $correspondent->contact, $correspondent->keptFor, $noting->facts, $noting->outcome, $gesture->at);
        $exchange->campaign = $noting->campaign();
        $exchange->importedFrom = $noting->importedFrom;
        $exchange->fingerprint = $noting->fingerprintWith($correspondent->identity);
        $exchange->origin = $gesture->origin;
        $exchange->recordThat(new ExchangeNoted($gesture->accountId, $gesture->by, $id, $gesture->at));

        return $exchange;
    }

    /**
     * Kept, like an exchange noted, for the organizations of its correspondent now.
     *
     * @throws PlannedExchangeInThePast
     */
    public static function planned(Gesture $gesture, ExchangeId $id, Correspondent $correspondent, ExchangeFacts $facts): self
    {
        self::ensureToCome($facts->occurredAt, $gesture->at);
        $exchange = new self($id, $gesture->accountId, $gesture->by, $correspondent->contact, $correspondent->keptFor, $facts, ExchangeStatus::Planned, $gesture->at);
        $exchange->origin = $gesture->origin;
        $exchange->recordThat(new ExchangePlanned($gesture->accountId, $gesture->by, $id, $gesture->at));

        return $exchange;
    }

    /**
     * Booked by the contact through the public link of its holder, the member of the gesture, who planned nothing
     * themselves (ADR 0164).
     *
     * @throws PlannedExchangeInThePast
     */
    public static function bookedByAContact(Gesture $gesture, ExchangeId $id, Correspondent $contact, ExchangeFacts $facts): self
    {
        $exchange = self::planned($gesture, $id, $contact, $facts);
        $exchange->bookedByLink = true;

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
        if (!self::isAMomentToCome($plannedAt, $at)) {
            throw PlannedExchangeInThePast::because('A planned exchange is in the future', 'occurredAt');
        }
    }

    /**
     * The only moments an exchange is planned at, or moved to.
     */
    public static function isAMomentToCome(\DateTimeImmutable $moment, \DateTimeImmutable $at): bool
    {
        return $moment > $at;
    }

    /**
     * A video call awaits the link it asked of the calendar of its holder while it is planned: done, missed or
     * cancelled, it is no longer pushed there (ADR 0137).
     */
    public static function awaitsALink(bool $linkRequested, ExchangeStatus $status): bool
    {
        return $linkRequested && ExchangeStatus::Planned === $status;
    }

    public function id(): ExchangeId
    {
        return $this->id;
    }

    /**
     * What tells it from any other exchange of its account, when it was noted (ADR 0166).
     */
    public function fingerprint(): ?ExchangeFingerprint
    {
        return $this->fingerprint;
    }

    /**
     * Noted by hand as an exchange already noted, it is told from it by nothing: the fingerprint stays the one of the
     * exchange noted first (ADR 0166).
     */
    public function leaveItsFingerprint(): void
    {
        $this->fingerprint = null;
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
     * again. Its place or its link stay while its channel has one: they are corrected by a gesture of their own.
     *
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public function correct(ExchangeFacts $facts, ExchangeStatus $outcome, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectExchange, $by, $at);
        self::ensureHappenedBefore($facts, $at);
        self::ensureMissable($facts->channel, $outcome);
        $this->facts = $facts->keepingTheVenueOf($this->facts);
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
     * Where a meeting is held, or the link of a video call, planned or done: a link may come once the video call is
     * planned. Not personal data: every prospector of the account corrects them.
     *
     * @throws PlaceOnlyForAMeeting|LinkOnlyForAVideoCall|NotPossibleInItsState
     */
    public function correctVenue(?string $place, ?VideoCallLink $link, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectVenue, $by, $at);
        $this->facts = $this->facts->withVenue($place, $link);
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeVenueCorrected($this->accountId, $by, $this->id, $at));
    }

    /**
     * The link the calendar of its holder created for it, while it still asks one: a link given meanwhile wins. A
     * correction by the system, without an author: its last correction stays that of a member (ADR 0137).
     */
    public function receiveLink(VideoCallLink $link, \DateTimeImmutable $at): void
    {
        if (!$this->facts->linkRequested) {
            return;
        }

        $this->facts = $this->facts->withVenue($this->facts->place, $link);
        $this->modifiedAt = $at;
        $this->recordThat(new VideoCallLinkReceived($this->accountId, $this->createdBy, $this->id, $at));
    }

    /**
     * Once its text retouched: its files, if any, are made conform by removing them.
     *
     * @throws NothingToRedact
     */
    public function declareConform(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MakeConform, $by, $at);
        $this->modifiedAt = $at;
        $this->madeConform = new Correction($by, $at);
        $this->recordThat(new ExchangeRedacted($this->accountId, $by, $this->id, $at));
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
     * To another moment to come, for another duration when one is given, else its own (ADR 0119).
     *
     * @throws NotUpcoming|NotTheHolder|PlannedExchangeInThePast
     */
    public function moveTo(\DateTimeImmutable $plannedAt, ?Duration $duration, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MoveExchange, $by, $at);
        self::ensureToCome($plannedAt, $at);
        $this->facts = $this->facts->movedTo($plannedAt, $duration);
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
        $this->erasing($by, $at, withItsOrganization: false);
    }

    /**
     * Erased as no organization keeps it any more, the prospecting of the last one stopped: no member erased it.
     */
    public function eraseWithItsOrganization(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->erasing($by, $at, withItsOrganization: true);
    }

    private function erasing(MemberId $by, \DateTimeImmutable $at, bool $withItsOrganization): void
    {
        $this->ensurePossible(ExchangeIntent::EraseExchange, $by, $at);
        $this->erasure = new Erasure($by, $at);
        $files = array_map(static fn (Attachment $attachment): string => $attachment->file, $this->attachments);
        $this->recordThat(new ExchangeErased($this->accountId, $by, $this->id, $files, $at, $this->contact, $this->campaign, $withItsOrganization));
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
     * A file of the e-mail it was imported from, its upload kept once the exchange is written (ADR 0182).
     *
     * @throws NoRoomForAnAttachment
     */
    public function importAttachment(Attachment $attachment, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::AttachFile, $by, $at);
        $this->attachments[] = $attachment;
        $this->modifiedAt = $at;
        $this->recordThat(new AttachmentImported($this->accountId, $by, $this->id, $attachment->id, $attachment->file, $at));
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
     * and its files to make conform (ADR 0079), its fingerprint forgotten (ADR 0166); still to come, it is cancelled,
     * whoever holds it.
     */
    public function forgetItsContact(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->contactForgotten = true;
        $this->fingerprint = null;
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

    public function isBookedByLink(): bool
    {
        return $this->bookedByLink;
    }

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }

    /**
     * Its contact waits to be attached: kept for no organization, its holder keeps it until then (ADR 0146).
     */
    public function contactToAttach(): ?PersonId
    {
        return self::isWithAContactToAttach(null !== $this->contact, $this->isKeptForAnOrganization()) ? $this->contact : null;
    }

    /**
     * With a contact and kept for no organization: its holder keeps it while the contact waits to be attached.
     */
    public static function isWithAContactToAttach(bool $withAContact, bool $keptForAnOrganization): bool
    {
        return $withAContact && !$keptForAnOrganization;
    }

    /**
     * The moment it happens, a meeting of its contact to attach.
     */
    public function happensAt(): \DateTimeImmutable
    {
        return $this->facts->occurredAt;
    }

    /**
     * Its contact to attach attached to an organization: the exchange is kept for it from now on (ADR 0146).
     */
    public function keepFor(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->keptFor = [$organization];
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeKeptFor($this->accountId, $by, $this->id, $organization, $at));
    }

    /**
     * Every intention starts with it: what the exchange does not offer the member now is refused.
     *
     * @throws NoRoomForAnAttachment|NothingToRedact|NotPossibleInItsState|NotTheHolder
     */
    private function ensurePossible(ExchangeIntent $intent, MemberId $by, \DateTimeImmutable $at): void
    {
        $situation = new ExchangeSituation(count($this->attachments), $this->awaitsBeingMadeConform(), $this->status, $this->facts->occurredAt <= $at, $this->createdBy->equals($by), $this->facts->channel);

        if (in_array($intent, ExchangePossibilities::given($situation), true)) {
            return;
        }

        throw match ($intent) {
            ExchangeIntent::CorrectVenue => NotPossibleInItsState::because('Only a meeting or a video call takes place somewhere'),
            ExchangeIntent::AttachFile => NoRoomForAnAttachment::because(sprintf('An exchange holds %d attachments at most', self::MOST_ATTACHMENTS)),
            ExchangeIntent::MakeConform => NothingToRedact::because('Only an exchange with a forgotten contact is to redact'),
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
