<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Event\AttachmentRemoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeContactForgotten;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMadeConform;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeTextCorrected;
use Prospecter\Core\Prospecting\Domain\Event\FileAttached;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
use Prospecter\Core\Prospecting\Domain\Exception\NoRoomForAnAttachment;
use Prospecter\Core\Prospecting\Domain\Exception\NothingToMakeConform;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownAttachment;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Exception\PersonalDataManagersOnly;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\RecordsEvents;
use Prospecter\Core\SharedKernel\Domain\ReleasesEvents;

/**
 * Aggregate root: a fact of the real world between a prospector of the account and a contact, or the
 * organization itself, a call, a message or a meeting; not a change made in the application, which the activity
 * journal keeps (ADR 0074, 0076). Its text may hold personal data: it is kept for the organizations the contact
 * was part of when it was noted, or for the organization itself, and lives as long as one of them is prospected.
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
        private readonly \DateTimeImmutable $createdAt,
    ) {
        $this->modifiedAt = $createdAt;
    }

    /**
     * @param list<OrganizationId> $organizationsOfTheContact
     *
     * @throws ExchangeInTheFuture
     */
    public static function notedWithAContact(AccountId $accountId, MemberId $by, ExchangeId $id, PersonId $contact, array $organizationsOfTheContact, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        return self::noted($accountId, $by, $id, $contact, $organizationsOfTheContact, $facts, $at);
    }

    /**
     * With the organization itself (its switchboard, its generic email): kept for it alone, in the timeline
     * of no contact.
     *
     * @throws ExchangeInTheFuture
     */
    public static function notedWithTheOrganization(Organization $organization, MemberId $by, ExchangeId $id, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        return self::noted($organization->accountId(), $by, $id, null, [$organization->id()], $facts, $at);
    }

    /**
     * @param list<OrganizationId> $keptFor
     */
    private static function noted(AccountId $accountId, MemberId $by, ExchangeId $id, ?PersonId $contact, array $keptFor, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        self::ensureHappenedBefore($facts, $at);
        $exchange = new self($id, $accountId, $by, $contact, $keptFor, $facts, $at);
        $exchange->recordThat(new ExchangeNoted($accountId, $by, $id, $at));

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
        foreach ($this->attachments as $attachment) {
            if ($attachment->id->equals($id)) {
                return $attachment;
            }
        }

        throw UnknownAttachment::identifiedBy($id);
    }

    private function allowsInItsState(ExchangeIntent $intent): bool
    {
        return in_array($intent, ExchangePossibilities::given(count($this->attachments), $this->awaitsBeingMadeConform()), true);
    }

    public function isTextFlaggedFor(Prospector $prospector): bool
    {
        return ExchangeReading::isTextFlagged(null !== $this->facts->text, $prospector);
    }

    /**
     * The contact stays: an exchange with somebody else is erased, then noted again.
     *
     * @throws ExchangeInTheFuture
     */
    public function correct(ExchangeFacts $facts, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectExchange);
        self::ensureHappenedBefore($facts, $at);
        $this->facts = $facts;
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeCorrected($this->accountId, $by, $this->id, $at));
    }

    /**
     * To make it conform, without the right to manage personal data too: its other facts stay.
     */
    public function correctText(?string $text, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectText);
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
        $this->ensurePossible(ExchangeIntent::MakeConform);
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
     * Erased from every timeline, once the repository removed it.
     */
    public function erase(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::EraseExchange);
        $this->recordThat(new ExchangeErased($this->accountId, $by, $this->id, $at));
    }

    /**
     * A file it brought, or that was sent with it.
     *
     * @throws NoRoomForAnAttachment
     */
    public function attach(Attachment $attachment, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::AttachFile);
        $this->attachments[] = $attachment;
        $this->modifiedAt = $at;
        $this->recordThat(new FileAttached($this->accountId, $by, $this->id, $attachment->id, $at));
    }

    /**
     * Gives back the file removed, for the client to remove it from the storage.
     *
     * @throws UnknownAttachment
     */
    public function removeAttachment(AttachmentId $id, MemberId $by, \DateTimeImmutable $at): Attachment
    {
        $this->ensurePossible(ExchangeIntent::RemoveAttachment);
        $removed = $this->attachment($id);
        $this->attachments = array_values(array_filter($this->attachments, static fn (Attachment $attachment): bool => $attachment !== $removed));
        $this->modifiedAt = $at;
        $this->recordThat(new AttachmentRemoved($this->accountId, $by, $this->id, $id, $at));

        return $removed;
    }

    /**
     * The account stopped prospecting this organization: kept for no organization any more, the exchange must
     * not stay (`isKeptForAnOrganization()`).
     */
    public function stopKeepingFor(OrganizationId $organization, MemberId $by, \DateTimeImmutable $at): void
    {
        $this->keptFor = array_values(array_filter($this->keptFor, static fn (OrganizationId $keptFor): bool => !$keptFor->equals($organization)));
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeNoLongerKeptFor($this->accountId, $by, $this->id, $organization, $at));
    }

    /**
     * Its contact erased by PersonalData: the exchange stays in the timelines of its organizations, its text
     * and its files to make conform (ADR 0079).
     */
    public function forgetItsContact(MemberId $by, \DateTimeImmutable $at): void
    {
        $this->contactForgotten = true;
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeContactForgotten($this->accountId, $by, $this->id, $at));
    }

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }

    /**
     * @throws NoRoomForAnAttachment|NothingToMakeConform
     */
    private function ensurePossible(ExchangeIntent $intent): void
    {
        if (ExchangeIntent::AttachFile === $intent && !$this->allowsInItsState($intent)) {
            throw NoRoomForAnAttachment::because(sprintf('An exchange holds %d attachments at most', self::MOST_ATTACHMENTS));
        }

        if (ExchangeIntent::MakeConform === $intent && !$this->allowsInItsState($intent)) {
            throw NothingToMakeConform::because('Only an exchange with a forgotten contact is to make conform');
        }
    }
}
