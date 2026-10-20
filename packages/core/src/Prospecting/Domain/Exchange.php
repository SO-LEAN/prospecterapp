<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain;

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

    /**
     * @param list<OrganizationId> $keptFor
     */
    private function __construct(
        private readonly ExchangeId $id,
        private readonly AccountId $accountId,
        private readonly MemberId $createdBy,
        private readonly ?PersonId $contact,
        private array $keptFor,
        private readonly ExchangeFacts $facts,
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

        if ($facts->occurredAt > $at) {
            throw ExchangeInTheFuture::because('An exchange is never in the future', 'occurredAt');
        }

        $exchange = new self($id, $prospector->accountId, $prospector->id, $contact, $keptFor, $facts, $at);
        $exchange->recordThat(new ExchangeNoted($prospector->accountId, $prospector->id, $id, $at));

        return $exchange;
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

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function modifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    /**
     * The account stopped prospecting this organization: kept for no organization any more, the exchange must
     * not stay (`isKeptForAnOrganization()`).
     *
     * @throws UnknownExchange
     */
    public function stopKeepingFor(OrganizationId $organization, Prospector $by, \DateTimeImmutable $at): void
    {
        if (!$by->accountId->equals($this->accountId)) {
            throw UnknownExchange::identifiedBy($this->id);
        }

        $this->keptFor = array_values(array_filter($this->keptFor, static fn (OrganizationId $keptFor): bool => !$keptFor->equals($organization)));
        $this->modifiedAt = $at;
        $this->recordThat(new ExchangeNoLongerKeptFor($this->accountId, $by->id, $this->id, $organization, $at));
    }

    public function isKeptForAnOrganization(): bool
    {
        return [] !== $this->keptFor;
    }
}
