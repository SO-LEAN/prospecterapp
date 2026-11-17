<?php

namespace App\Campaigns\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\Campaigns\Domain\Model\ContactFact;
use Prospecter\Core\Campaigns\Domain\Model\Fact;
use Prospecter\Core\Campaigns\Domain\Model\Locality;
use Prospecter\Core\Campaigns\Domain\Model\OrganizationFact;
use Prospecter\Core\Campaigns\Domain\Model\Reach;
use Prospecter\Core\Campaigns\Domain\Repository\FactRepository;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Psr\Clock\ClockInterface;

/**
 * The facts the tags of the contacts derive from, in the table of Campaigns (ADR 0141): identities and booleans, no
 * personal data. A fact is written in place of the one it replaces under the condition that it is still the one kept;
 * the fact of an organization writes, in the same transaction, a link per contact it names that the replaced one did
 * not name, and removes those of the contacts it no longer names, so that the tags of a contact are read by its keys.
 * A terminal fact is forgotten 15 days later, longer than an event may wait (6 hours in the worker, a day of retries of
 * EventBridge, 14 days in the failures).
 *
 * PK `ACCOUNT#<account>`, SK `FACT#ORGANIZATION#<id>` (`revision`, `present`, `contacts`, `municipalityCode` and
 * `country`, ADR 0142, absent from the facts recorded before), `FACT#CONTACT#<id>`
 * (`revision`, `present`, `hasEmail`, `hasPhone`, `hasMobile`, `hasLinkedIn`) and its links
 * `FACT#CONTACT#<id>#ORGANIZATION#<organization>` (`organization`), `expiresAt` the epoch a terminal fact is forgotten at.
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
     * @return list<Write> the links of the contacts the fact of an organization names now and did not, and the removal
     *                     of those it no longer names
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
        return array_map(static fn (PersonId $contact): string => $contact->value, $fact->contacts);
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
     * @return array<string, mixed> what a fact of its kind carries beyond its revision and its presence
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
        $revision = (int) $item['revision'];
        $present = (bool) $item['present'];

        return match (true) {
            $about instanceof OrganizationFact => new OrganizationFact($about->accountId, new OrganizationId($about->id->value), $revision, $present, array_map(static fn (string $contact): PersonId => new PersonId($contact), $item['contacts']), Locality::read(OrganizationFactItem::localityIn($item))),
            $about instanceof ContactFact => new ContactFact($about->accountId, new PersonId($about->id->value), $revision, $present, new Reach((bool) $item['hasEmail'], (bool) $item['hasPhone'], (bool) $item['hasMobile'], (bool) $item['hasLinkedIn'])),
            default => throw new \LogicException(sprintf('No item keeps a %s', $about::class)),
        };
    }
}
