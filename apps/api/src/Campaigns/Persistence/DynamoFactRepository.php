<?php

declare(strict_types=1);

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use App\Shared\Typed;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\ContactId;
use Prospecter\Core\Campaigns\Domain\Model\Fact;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationId;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;
use Psr\Clock\ClockInterface;

/**
 * The facts that the contacts' tags are derived from, in Campaigns' table (ADR 0141). They hold ids and booleans, and
 * no personal data. A fact is written in place of the one it replaces, on condition that the replaced fact is still the
 * kept one. In the same transaction, an organization's fact writes a link for each contact it names that the replaced
 * fact did not name. It also removes the links of the contacts it no longer names. So a contact's tags are read by its
 * keys. A terminal fact is forgotten 15 days later. That is longer than an event may wait: 6 hours in the worker, a day
 * of EventBridge retries, and 14 days among the failures.
 *
 * PK `ACCOUNT#<account>`. The SKs are:
 *
 * - `FACT#ORGANIZATION#<id>` (`revision`, `present`, `contacts`, `municipalityCode` and `country`). The last two
 *   came with ADR 0142, and the facts recorded before lack them;
 * - `FACT#CONTACT#<id>` (`revision`, `present`, `hasEmail`, `hasPhone`, `hasMobile`, `hasLinkedIn`);
 * - its links, `FACT#CONTACT#<id>#ORGANIZATION#<organization>` (`organization`).
 *
 * `expiresAt` is the epoch at which a terminal fact is forgotten.
 */
final readonly class DynamoFactRepository implements FactRepository
{
    private const string NEW_FACT = 'attribute_not_exists(SK)';
    private const string FACT_KEPT = 'revision = :revision AND present = :present';
    private const string TERMINAL_KEPT_FOR = '+15 days';

    public function __construct(private Table $campaignsTable, private ClockInterface $clock)
    {
    }

    public function find(Fact $about): ?Fact
    {
        $item = $this->campaignsTable->item(CampaignsKeys::account($about->accountId->value), self::keyOf($about));

        return null === $item ? null : self::factOf($about, $item);
    }

    public function record(Fact $fact, ?Fact $replaced): void
    {
        $this->campaignsTable->transact([$this->factWrite($fact, $replaced), ...$this->linkWrites($fact, $replaced)]);
    }

    private function factWrite(Fact $fact, ?Fact $replaced): Write
    {
        $item = [
            'PK' => CampaignsKeys::account($fact->accountId->value),
            'SK' => self::keyOf($fact),
            'revision' => $fact->revision,
            'present' => $fact->present,
            ...self::attributesOf($fact),
            ...($fact->present ? [] : ['expiresAt' => $this->clock->now()->modify(self::TERMINAL_KEPT_FOR)->getTimestamp()]),
        ];

        return null === $replaced
            ? $this->campaignsTable->replacing($item, self::NEW_FACT)
            : $this->campaignsTable->replacing($item, self::FACT_KEPT, [':revision' => $replaced->revision, ':present' => $replaced->present]);
    }

    /**
     * @return list<Write> the links of the contacts that an organization's fact names now and did not name before,
     *                     and the removal of the links of those it no longer names
     */
    private function linkWrites(Fact $fact, ?Fact $replaced): array
    {
        if (!$fact instanceof OrganizationFact) {
            return [];
        }

        $named = self::contactsNamedBy($fact);
        $namedBefore = $replaced instanceof OrganizationFact ? self::contactsNamedBy($replaced) : [];
        $account = CampaignsKeys::account($fact->accountId->value);

        return [
            ...array_map(fn (string $contact): Write => $this->campaignsTable->putting(['PK' => $account, 'SK' => CampaignsKeys::link($contact, $fact->id->value), 'organization' => $fact->id->value]), array_values(array_diff($named, $namedBefore))),
            ...array_map(fn (string $contact): Write => $this->campaignsTable->deleting($account, CampaignsKeys::link($contact, $fact->id->value)), array_values(array_diff($namedBefore, $named))),
        ];
    }

    /**
     * @return list<string>
     */
    private static function contactsNamedBy(OrganizationFact $fact): array
    {
        return array_map(static fn (ContactId $contact): string => $contact->value, $fact->contacts);
    }

    private static function keyOf(Fact $fact): string
    {
        return match (true) {
            $fact instanceof OrganizationFact => CampaignsKeys::organizationFact($fact->id->value),
            $fact instanceof ContactFact => CampaignsKeys::contactFact($fact->id->value),
            default => throw new \LogicException(sprintf('No item keeps a %s', $fact::class)),
        };
    }

    /**
     * @return array{contacts: list<string>, municipalityCode: ?string, country: ?string}|array{hasEmail: bool, hasPhone: bool, hasMobile: bool, hasLinkedIn: bool}|array{} what a fact of its kind carries besides its revision and presence
     */
    private static function attributesOf(Fact $fact): array
    {
        return match (true) {
            $fact instanceof OrganizationFact => ['contacts' => self::contactsNamedBy($fact), 'municipalityCode' => $fact->locality->municipalityCode, 'country' => $fact->locality->country],
            $fact instanceof ContactFact => ['hasEmail' => $fact->reach->email, 'hasPhone' => $fact->reach->phone, 'hasMobile' => $fact->reach->mobile, 'hasLinkedIn' => $fact->reach->linkedIn],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $item
     */
    private static function factOf(Fact $about, array $item): Fact
    {
        $revision = Typed::int($item['revision']);
        $present = Typed::bool($item['present']);

        return match (true) {
            $about instanceof OrganizationFact => new OrganizationFact($about->accountId, new OrganizationId($about->id->value), $revision, $present, array_map(static fn (string $contact): ContactId => new ContactId($contact), Typed::strings($item['contacts'])), Locality::read(OrganizationFactItem::localityIn($item))),
            $about instanceof ContactFact => new ContactFact($about->accountId, new ContactId($about->id->value), $revision, $present, new Reach(Typed::bool($item['hasEmail']), Typed::bool($item['hasPhone']), Typed::bool($item['hasMobile']), Typed::bool($item['hasLinkedIn']))),
            default => throw new \LogicException(sprintf('No item keeps a %s', $about::class)),
        };
    }
}
