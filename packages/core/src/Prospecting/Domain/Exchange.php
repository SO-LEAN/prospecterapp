<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

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
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownOrganization;
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
     * @throws PersonalDataManagersOnly|ExchangeInTheFuture
     */
    public static function notedBy(Prospector $prospector, ExchangeId $id, PersonId $contact, array $organizationsOfTheContact, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        return self::noted($prospector, $id, $contact, $organizationsOfTheContact, $facts, $at);
    }

    /**
     * With the organization itself (its switchboard, its generic email): kept for it alone, in the timeline
     * of no contact.
     *
     * @throws UnknownOrganization|PersonalDataManagersOnly|ExchangeInTheFuture
     */
    public static function notedWithTheOrganization(Organization $organization, Prospector $prospector, ExchangeId $id, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        $organization->ensureReachableBy($prospector);

        return self::noted($prospector, $id, null, [$organization->id()], $facts, $at);
    }

    /**
     * @param list<OrganizationId> $keptFor
     */
    private static function noted(Prospector $prospector, ExchangeId $id, ?PersonId $contact, array $keptFor, ExchangeFacts $facts, \DateTimeImmutable $at): self
    {
        $prospector->ensureMayExchangeWithTheContacts();
        self::ensureHappenedBefore($facts, $at);
        $exchange = new self($id, $prospector->accountId, $prospector->id, $contact, $keptFor, $facts, $at);
        $exchange->recordThat(new ExchangeNoted($prospector->accountId, $prospector->id, $id, $at));

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
     * Who noted it, for the record: every prospector of the account reads it alike.
     */
    public function createdBy(): MemberId
    {
        return $this->createdBy;
    }

    /**
     * The contact it was with, none when it was with the organization itself.
     */
    public function contact(): ?PersonId
    {
        return $this->contact;
    }

    /**
     * The contact still known, or forgotten; none for the organization itself.
     */
    public function contactPresence(): ?ContactPresence
    {
        if (null === $this->contact) {
            return null;
        }

        return $this->contactForgotten ? ContactPresence::Forgotten : ContactPresence::Present;
    }

    /**
     * The contact still one of this organization, removed from it, or forgotten; none for the organization
     * itself.
     */
    public function contactPresenceIn(Organization $organization): ?ContactPresence
    {
        if (null === $this->contact || $this->contactForgotten) {
            return $this->contactPresence();
        }

        return $organization->hasContact($this->contact) ? ContactPresence::Present : ContactPresence::Removed;
    }

    /**
     * The organizations the contact was part of when the exchange was noted, still prospected.
     *
     * @return list<OrganizationId>
     */
    public function keptFor(): array
    {
        return $this->keptFor;
    }

    public function facts(): ExchangeFacts
    {
        return $this->facts;
    }

    /**
     * Who corrected the exchange last, and when; null while it is as it was noted. Its former versions are
     * kept by the activity journal only.
     */
    public function lastCorrection(): ?Correction
    {
        return $this->lastCorrection;
    }

    /**
     * Who declared it conform, and when; null while it is not.
     */
    public function madeConform(): ?Correction
    {
        return $this->madeConform;
    }

    /**
     * Its contact forgotten, it may still hold their personal data: in its text until it is declared conform,
     * in its files until they are removed.
     */
    public function isToMakeConform(): bool
    {
        return $this->awaitsBeingMadeConform() || $this->areAttachmentsToMakeConform() && [] !== $this->attachments;
    }

    public function isTextToMakeConform(): bool
    {
        return null !== $this->facts->text && $this->awaitsBeingMadeConform();
    }

    /**
     * A file is made conform by removing it.
     */
    public function areAttachmentsToMakeConform(): bool
    {
        return $this->contactForgotten;
    }

    private function awaitsBeingMadeConform(): bool
    {
        return $this->contactForgotten && null === $this->madeConform;
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
        return $prospector->managesPersonalData ? $this->attachments : [];
    }

    /**
     * A file joined to it, hidden to a prospector without the right to manage personal data (ADR 0078).
     *
     * @throws UnknownExchange|PersonalDataManagersOnly|UnknownAttachment
     */
    public function attachmentReadBy(Prospector $prospector, AttachmentId $id): Attachment
    {
        $this->ensureReachableBy($prospector);
        $prospector->ensureMayExchangeWithTheContacts();

        foreach ($this->attachments as $attachment) {
            if ($attachment->id->equals($id)) {
                return $attachment;
            }
        }

        throw UnknownAttachment::identifiedBy($id);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function modifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    /**
     * @return list<ExchangeIntent>
     */
    public function possibleIntentsOf(Prospector $prospector): array
    {
        if (!$this->belongsTo($prospector)) {
            return [];
        }

        return array_values(array_filter($this->intentsOpenTo($prospector), $this->allowsInItsState(...)));
    }

    /**
     * Retouching its text, and declaring it conform, are open to every prospector of the account; the rest
     * needs the right to manage personal data.
     *
     * @return list<ExchangeIntent>
     */
    private function intentsOpenTo(Prospector $prospector): array
    {
        return $prospector->managesPersonalData ? ExchangeIntent::cases() : [ExchangeIntent::CorrectText, ExchangeIntent::MakeConform];
    }

    private function allowsInItsState(ExchangeIntent $intent): bool
    {
        return match ($intent) {
            ExchangeIntent::AttachFile => $this->hasRoomForAnAttachment(),
            ExchangeIntent::MakeConform => $this->awaitsBeingMadeConform(),
            default => true,
        };
    }

    private function hasRoomForAnAttachment(): bool
    {
        return count($this->attachments) < self::MOST_ATTACHMENTS;
    }

    /**
     * Every prospector of the account reads the text; without the right to manage personal data, it comes
     * flagged: it may hold some.
     */
    public function isTextFlaggedFor(Prospector $prospector): bool
    {
        return null !== $this->facts->text && !$prospector->managesPersonalData;
    }

    /**
     * The contact stays: an exchange with somebody else is erased, then noted again.
     *
     * @throws UnknownExchange|PersonalDataManagersOnly|ExchangeInTheFuture
     */
    public function correct(ExchangeFacts $facts, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectExchange, $by);
        self::ensureHappenedBefore($facts, $at);
        $this->facts = $facts;
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeCorrected($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * To make it conform, without the right to manage personal data too: its other facts stay.
     *
     * @throws UnknownExchange
     */
    public function correctText(?string $text, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::CorrectText, $by);
        $this->facts = $this->facts->withText($text);
        $this->correctedBy($by, $at);
        $this->recordThat(new ExchangeTextCorrected($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * Once its text retouched: its files, if any, are made conform by removing them.
     *
     * @throws UnknownExchange|NothingToMakeConform
     */
    public function declareConform(Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::MakeConform, $by);
        $this->modifiedAt = $at;
        $this->madeConform = new Correction($by->id, $at);
        $this->recordThat(new ExchangeMadeConform($this->accountId, $by->id, $this->id, $at));
    }

    private function correctedBy(Prospector $prospector, \DateTimeImmutable $at): void
    {
        $this->modifiedAt = $at;
        $this->lastCorrection = new Correction($prospector->id, $at);
    }

    /**
     * Erased from every timeline, once the repository removed it.
     *
     * @throws UnknownExchange|PersonalDataManagersOnly
     */
    public function erase(Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::EraseExchange, $by);
        $this->recordThat(new ExchangeErased($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * A file it brought, or that was sent with it.
     *
     * @throws UnknownExchange|PersonalDataManagersOnly|NoRoomForAnAttachment
     */
    public function attach(Attachment $attachment, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::AttachFile, $by);
        $this->attachments[] = $attachment;
        $this->modifiedAt = $at;
        $this->recordThat(new FileAttached($this->accountId, $by->id, $this->id, $attachment->id, $at));
    }

    /**
     * @throws UnknownExchange|PersonalDataManagersOnly|UnknownAttachment
     */
    public function removeAttachment(AttachmentId $id, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensurePossible(ExchangeIntent::RemoveAttachment, $by);
        $removed = $this->attachmentReadBy($by, $id);
        $this->attachments = array_values(array_filter($this->attachments, static fn (Attachment $attachment): bool => $attachment !== $removed));
        $this->modifiedAt = $at;
        $this->recordThat(new AttachmentRemoved($this->accountId, $by->id, $this->id, $id, $at));
    }

    /**
     * The account stopped prospecting this organization: kept for no organization any more, the exchange must
     * not stay (`isKeptForAnOrganization()`).
     *
     * @throws UnknownExchange
     */
    public function stopKeepingFor(OrganizationId $organization, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensureReachableBy($by);
        $this->keptFor = array_values(array_filter($this->keptFor, static fn (OrganizationId $keptFor): bool => !$keptFor->equals($organization)));
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeNoLongerKeptFor($this->accountId, $by->id, $this->id, $organization, $at));
    }

    /**
     * Its contact erased by PersonalData: the exchange stays in the timelines of its organizations, its text
     * and its files to make conform (ADR 0079).
     *
     * @throws UnknownExchange
     */
    public function forgetItsContact(Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensureReachableBy($by);
        $this->contactForgotten = true;
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeContactForgotten($this->accountId, $by->id, $this->id, $at));
    }

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }

    /**
     * @throws UnknownExchange|PersonalDataManagersOnly|NoRoomForAnAttachment|NothingToMakeConform
     */
    private function ensurePossible(ExchangeIntent $intent, Prospector $by): void
    {
        $this->ensureReachableBy($by);

        if (!in_array($intent, $this->intentsOpenTo($by), true)) {
            throw PersonalDataManagersOnly::because('Only a prospector allowed to manage personal data may exchange with the contacts');
        }

        if (ExchangeIntent::AttachFile === $intent && !$this->hasRoomForAnAttachment()) {
            throw NoRoomForAnAttachment::because(sprintf('An exchange holds %d attachments at most', self::MOST_ATTACHMENTS));
        }

        if (ExchangeIntent::MakeConform === $intent && !$this->awaitsBeingMadeConform()) {
            throw NothingToMakeConform::because('Only an exchange with a forgotten contact is to make conform');
        }
    }

    /**
     * Another account does not even learn that the exchange exists.
     *
     * @throws UnknownExchange
     */
    public function ensureReachableBy(Prospector $prospector): void
    {
        if (!$this->belongsTo($prospector)) {
            throw UnknownExchange::identifiedBy($this->id);
        }
    }

    private function belongsTo(Prospector $prospector): bool
    {
        return $prospector->accountId->equals($this->accountId);
    }
}
