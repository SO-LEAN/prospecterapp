<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoLongerKeptFor;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Exception\ExchangeInTheFuture;
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
 */
final class Exchange implements ReleasesEvents
{
    use RecordsEvents;

    private \DateTimeImmutable $modifiedAt;
    private ?Correction $lastCorrection = null;

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
        return $this->belongsTo($prospector) && $prospector->managesPersonalData ? ExchangeIntent::cases() : [];
    }

    /**
     * What was said is personal data: it is read with the right to manage them, within the account only.
     *
     * @throws UnknownExchange|PersonalDataManagersOnly
     */
    public function ensureReadableBy(Prospector $prospector): void
    {
        $this->ensureReachableBy($prospector);
        $prospector->ensureMayExchangeWithTheContacts();
    }

    /**
     * The contact stays: an exchange with somebody else is erased, then noted again.
     *
     * @throws UnknownExchange|PersonalDataManagersOnly|ExchangeInTheFuture
     */
    public function correct(ExchangeFacts $facts, Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensureReadableBy($by);
        self::ensureHappenedBefore($facts, $at);
        $this->facts = $facts;
        $this->modifiedAt = $at;
        $this->lastCorrection = new Correction($by->id, $at);
        $this->recordThat(new ExchangeCorrected($this->accountId, $by->id, $this->id, $at));
    }

    /**
     * Erased from every timeline, once the repository removed it.
     *
     * @throws UnknownExchange|PersonalDataManagersOnly
     */
    public function erase(Prospector $by, \DateTimeImmutable $at): void
    {
        $this->ensureReadableBy($by);
        $this->recordThat(new ExchangeErased($this->accountId, $by->id, $this->id, $at));
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

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }

    /**
     * Another account does not even learn that the exchange exists.
     *
     * @throws UnknownExchange
     */
    private function ensureReachableBy(Prospector $prospector): void
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
