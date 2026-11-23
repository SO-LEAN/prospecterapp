<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\AttachmentImported;
use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCancelled;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeJoinedCampaign;
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
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeAlreadyNoted;
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
use Prospecter\Core\SharedKernel\Domain\Exception\NotPossibleInItsState;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a real-world fact between one of the account's prospectors and a contact, or the organization
 * itself. It is a call, a message or a meeting. It is not a change made in the application (ADR 0074, 0076).
 * A planned exchange is still to come. Its author, the member who planned it, holds it. Once its time has come,
 * only the holder marks it done or missed. Only the holder moves it or cancels it (ADR 0111, 0112).
 * Its text may hold personal data. The exchange is kept for the organizations the contact was part of when it was
 * logged, or for the organization itself. It lives as long as one of them is prospected.
 * When its contact is forgotten, the exchange stays and must be redacted (ADR 0079). An exchange logged for a
 * campaign's recipient keeps the campaign (ADR 0145).
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
     * A logged exchange is done, or missed when the contact did not answer or come. It is kept for its
     * correspondent's organizations. An exchange logged for a running campaign's pending recipient keeps the campaign
     * (ADR 0145). An exchange imported from the member's mailbox keeps the e-mail (ADR 0154). Its fingerprint comes
     * from where it was read, when the caller gives it (ADR 0166). The member logs it by hand, or an AI logs it for
     * them (ADR 0185, 0186).
     *
     * @throws CampaignNotRunning|UnknownRecipient|RecipientAlreadyProcessed
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public static function noted(Gesture $gesture, ExchangeId $id, Correspondent $correspondent, Noting $noting): self
    {
        $correspondent->ensureToHandle();
        self::ensureHappenedBefore($noting->facts, $gesture->at);
        $noting->ensureMissable();
        $exchange = new self($id, $gesture->accountId, $gesture->by, $correspondent->contact, $correspondent->keptFor, $noting->facts, $noting->outcome, $gesture->at);
        $exchange->campaign = $correspondent->campaign();
        $exchange->importedFrom = $noting->importedFrom;
        $exchange->fingerprint = $noting->fingerprintWith($correspondent->identity);
        $exchange->origin = $gesture->origin;
        $exchange->recordThat(new ExchangeNoted($gesture->accountId, $gesture->by, $id, $gesture->at));

        return $exchange;
    }

    /**
     * Like a logged exchange, a planned exchange is kept for its correspondent's current organizations.
     *
     * @throws PlannedExchangeInThePast
     */
    public static function planned(Gesture $gesture, ExchangeId $id, ContactCorrespondent|OrganizationCorrespondent $correspondent, ExchangeFacts $facts): self
    {
        self::ensureToCome($facts->occurredAt, $gesture->at);
        $exchange = new self($id, $gesture->accountId, $gesture->by, $correspondent->contact, $correspondent->keptFor, $facts, ExchangeStatus::Planned, $gesture->at);
        $exchange->origin = $gesture->origin;
        $exchange->recordThat(new ExchangePlanned($gesture->accountId, $gesture->by, $id, $gesture->at));

        return $exchange;
    }

    /**
     * The contact booked this exchange through its holder's public link. The holder is the gesture's member, who
     * planned nothing themselves (ADR 0164).
     *
     * @throws PlannedExchangeInThePast
     */
    public static function bookedByAContact(Gesture $gesture, ExchangeId $id, ContactCorrespondent $contact, ExchangeFacts $facts): self
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
     * @throws PlannedExchangeInThePast
     */
    private static function ensureToCome(\DateTimeImmutable $plannedAt, \DateTimeImmutable $at): void
    {
        if (!self::isAMomentToCome($plannedAt, $at)) {
            throw PlannedExchangeInThePast::because('A planned exchange is in the future', 'occurredAt');
        }
    }

    /**
     * Tells whether a moment is to come. An exchange is planned at such a moment, or moved to one, and never at
     * another.
     */
    public static function isAMomentToCome(\DateTimeImmutable $moment, \DateTimeImmutable $at): bool
    {
        return $moment > $at;
    }

    /**
     * A video call awaits the link it requested from its holder's calendar while it is planned. Once it is done,
     * missed or cancelled, Prospecter no longer pushes it there (ADR 0137).
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
     * What tells it apart from the account's other exchanges, taken when it was logged (ADR 0166).
     */
    public function fingerprint(): ?ExchangeFingerprint
    {
        return $this->fingerprint;
    }

    /**
     * A member logged by hand an exchange that was already logged. Nothing tells the two apart, so the fingerprint
     * stays with the exchange logged first (ADR 0166).
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
     * When its contact is forgotten, the exchange may still hold their personal data. Its text holds them until a
     * member declares it redacted. Its files hold them until a member removes them.
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
     * The files attached to it, in the order they were attached.
     *
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return $this->attachments;
    }

    /**
     * The attached files a prospector reads. A prospector without the right to manage personal data reads none
     * (ADR 0078).
     *
     * @return list<Attachment>
     */
    public function attachmentsReadBy(Prospector $prospector): array
    {
        return ExchangeReading::readsTheAttachments($prospector) ? $this->attachments : [];
    }

    /**
     * One attached file. It is hidden from a prospector without the right to manage personal data (ADR 0078).
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
     * A member corrects an exchange once it took place, as done or missed. The contact stays. An exchange with
     * somebody else is erased, then logged again. Its place or its link stays while its channel has one. A gesture
     * of their own corrects them.
     *
     * @throws ExchangeInTheFuture|ChannelNotMissed
     */
    public function correct(ExchangeFacts $facts, ExchangeStatus $outcome, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectExchange, $by, $at);
        self::ensureHappenedBefore($facts, $at);
        $facts->ensureMissable($outcome);
        $this->facts = $facts->keepingTheVenueOf($this->facts);
        $this->status = $outcome;
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeCorrected($this->accountId, $by, $this->id, $at));
    }

    /**
     * The exchange was logged first outside any campaign. For example, a mailbox's import logs an e-mail before the
     * AI that sent it does. The exchange becomes the recipient's exchange once the AI logs the same sending. This is
     * a correction by the AI's member (ADR 0195). Any other exchange with that fingerprint stays already logged.
     *
     * @throws ExchangeAlreadyNoted|CampaignNotRunning|UnknownRecipient|RecipientAlreadyProcessed
     */
    public function joinCampaign(RecipientInCampaign $recipient, MemberId $by, \DateTimeImmutable $at): void
    {
        if (!$this->isTheSendingTo($recipient)) {
            throw ExchangeAlreadyNoted::because('Exchange already noted', context: ['exchange' => $this->id->value]);
        }

        $recipient->ensureToHandle();
        $this->campaign = $recipient->campaign;
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeJoinedCampaign($this->accountId, $by, $this->id, $at));
    }

    private function isTheSendingTo(RecipientInCampaign $recipient): bool
    {
        return null === $this->campaign
            && true === $this->contact?->equals($recipient->contact)
            && $recipient->channel === $this->facts->channel
            && Direction::Outbound === $this->facts->direction;
    }

    /**
     * A member corrects the text to redact it, even without the right to manage personal data. The other facts
     * stay.
     */
    public function correctText(?string $text, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectText, $by, $at);
        $this->facts = $this->facts->withText($text);
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeTextCorrected($this->accountId, $by, $this->id, $at));
    }

    /**
     * Corrects where a meeting is held, or a video call's link, whether the exchange is planned or done. A link may
     * come after the video call is planned. Neither is personal data, so every prospector of the account corrects
     * them.
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
     * Keeps the link that its holder's calendar created for it, while the exchange still requests one. A link given
     * meanwhile wins. The system makes this correction, without an author. The last correction stays a member's
     * (ADR 0137).
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
     * A member declares the exchange redacted once they have edited its text. Its files, if any, are redacted by
     * removing them.
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
     * Its time has come and it took place. Its report replaces its agenda. Its moment becomes the moment it really
     * took place.
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
     * Its time has come, and the contact did not come or answer.
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
     * Moves the exchange to another moment to come. It takes the new duration when one is given, and keeps its own
     * otherwise (ADR 0119).
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
     * The cancelled exchange stays in the timelines. It keeps who called it off, the prospector or the contact, when
     * the member says so.
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
     * The exchange is erased from every timeline once the repository removes it.
     */
    public function erase(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->erasing($by, $at, withItsOrganization: false);
    }

    /**
     * The account stopped prospecting the last organization that kept the exchange, so no organization keeps it any
     * more. No member erased it.
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
     * Attaches a file of the e-mail the exchange was imported from. Its upload is kept once the exchange is written
     * (ADR 0182).
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
     * The account stopped prospecting this organization. An exchange that no organization keeps any more must not
     * stay (ExchangeLeftWithoutOrganization).
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
     * PersonalData erased its contact. The exchange stays in its organizations' timelines. Its text and its files
     * must be redacted (ADR 0079). Its fingerprint is forgotten (ADR 0166). An exchange still to come is cancelled,
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
     * The contact who waits to be attached. No organization keeps the exchange, and its holder keeps it until then
     * (ADR 0146).
     */
    public function contactToAttach(): ?PersonId
    {
        return self::isWithAContactToAttach(null !== $this->contact, $this->isKeptForAnOrganization()) ? $this->contact : null;
    }

    /**
     * An exchange with a contact that no organization keeps is with a contact to attach. Its holder keeps it while
     * the contact waits to be attached.
     */
    public static function isWithAContactToAttach(bool $withAContact, bool $keptForAnOrganization): bool
    {
        return $withAContact && !$keptForAnOrganization;
    }

    /**
     * The moment it happens. It dates a meeting with its contact to attach.
     */
    public function happensAt(): \DateTimeImmutable
    {
        return $this->facts->occurredAt;
    }

    /**
     * A member attached its contact to an organization. From now on, that organization keeps the exchange
     * (ADR 0146).
     */
    public function keepFor(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->keptFor = [$organization];
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeKeptFor($this->accountId, $by, $this->id, $organization, $at));
    }

    /**
     * Every intention starts here. It refuses what the exchange does not offer the member now.
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
     * The exchange is no longer to come, another member holds it, or its time has not come.
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
