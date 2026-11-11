<?php

namespace App\Reporting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Persistence\Write;
use Prospecter\Core\Reporting\Domain\Model\ContactFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeFact;
use Prospecter\Core\Reporting\Domain\Model\ExchangeId;
use Prospecter\Core\Reporting\Domain\Model\Fact;
use Prospecter\Core\Reporting\Domain\Model\FiguresChange;
use Prospecter\Core\Reporting\Domain\Model\Month;
use Prospecter\Core\Reporting\Domain\Model\OrganizationFact;
use Prospecter\Core\Reporting\Domain\Repository\FactRepository;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Psr\Clock\ClockInterface;

/**
 * The facts of a version and the figures they make, in the table of Reporting: no personal data, identities and
 * booleans. A fact is written in place of the one it replaces under the condition that the version still keeps it,
 * with the additions of its change to the figures of its account, in one transaction; a terminal fact is forgotten
 * 15 days later, longer than an event may wait (6 hours in the worker, a day of retries of EventBridge, 14 days in
 * the failures).
 *
 * PK `FACTS#<version>#<account>`, SK `ORGANIZATION#<id>` (`revision`, `present`, `located`), `EXCHANGE#<id>`
 * (`revision`, `present`, `month`, YYYY-MM, null without a moment) or `CONTACT#<id>` (`revision`, `present`),
 * `expiresAt` the epoch a terminal fact is forgotten at; PK `FIGURES#<version>#<account>`, SK `TOTAL`
 * (`activeOrganizations`, `locatedOrganizations`, `contacts`, `exchanges`) and `MONTH#<YYYY-MM>` (`exchanges`).
 */
final readonly class DynamoFactRepository implements FactRepository
{
    private const string NEW_FACT = 'attribute_not_exists(SK)';
    private const string FACT_KEPT = 'revision = :revision AND present = :present';
    private const string TERMINAL_KEPT_FOR = '+15 days';

    public function __construct(private Table $reportingTable, private ClockInterface $clock)
    {
    }

    public function find(int $version, Fact $about): ?Fact
    {
        $item = $this->reportingTable->item(ReportingKeys::facts($version, $about->accountId), ReportingKeys::fact($about));

        return null === $item ? null : self::factOf($about, $item);
    }

    public function record(int $version, Fact $fact, ?Fact $replaced, FiguresChange $change): void
    {
        $this->reportingTable->transact([$this->factWrite($version, $fact, $replaced), ...$this->figuresWrites($version, $fact, $change)]);
    }

    private function factWrite(int $version, Fact $fact, ?Fact $replaced): Write
    {
        $item = [
            'PK' => ReportingKeys::facts($version, $fact->accountId),
            'SK' => ReportingKeys::fact($fact),
            'revision' => $fact->revision,
            'present' => $fact->present,
            ...self::attributesOf($fact),
            ...($fact->present ? [] : ['expiresAt' => $this->clock->now()->modify(self::TERMINAL_KEPT_FOR)->getTimestamp()]),
        ];

        return null === $replaced
            ? $this->reportingTable->replacing($item, self::NEW_FACT)
            : $this->reportingTable->replacing($item, self::FACT_KEPT, [':revision' => $replaced->revision, ':present' => $replaced->present]);
    }

    /**
     * @return list<Write> the additions of the change to the figures it changes
     */
    private function figuresWrites(int $version, Fact $fact, FiguresChange $change): array
    {
        $figures = ReportingKeys::figures($version, $fact->accountId);
        $total = array_filter([
            'activeOrganizations' => $change->activeOrganizations,
            'locatedOrganizations' => $change->locatedOrganizations,
            'contacts' => $change->contacts,
            ReportingKeys::EXCHANGES => $change->exchanges,
        ]);
        $writes = [] === $total ? [] : [$this->reportingTable->adding($figures, ReportingKeys::TOTAL, $total)];

        foreach (array_filter($change->exchangesByMonth) as $month => $difference) {
            $writes[] = $this->reportingTable->adding($figures, ReportingKeys::month(new Month($month)), [ReportingKeys::EXCHANGES => $difference]);
        }

        return $writes;
    }

    /**
     * @return array<string, mixed> what a fact of its kind carries beyond its revision and its presence
     */
    private static function attributesOf(Fact $fact): array
    {
        return match (true) {
            $fact instanceof OrganizationFact => ['located' => $fact->located],
            $fact instanceof ExchangeFact => ['month' => null === $fact->notedAt ? null : Month::of($fact->notedAt)->value],
            default => [],
        };
    }

    /**
     * The fact as the version keeps it, of the kind of the one it is about; an exchange noted at the first moment of
     * its month, the moment Reporting counts.
     *
     * @param array<string, mixed> $item
     */
    private static function factOf(Fact $about, array $item): Fact
    {
        $revision = (int) $item['revision'];
        $present = (bool) $item['present'];

        return match (true) {
            $about instanceof OrganizationFact => new OrganizationFact($about->accountId, new OrganizationId($about->id->value), $revision, $present, (bool) $item['located']),
            $about instanceof ExchangeFact => new ExchangeFact($about->accountId, new ExchangeId($about->id->value), $revision, $present, null === $item['month'] ? null : new \DateTimeImmutable($item['month'].'-01T00:00:00Z')),
            $about instanceof ContactFact => new ContactFact($about->accountId, new PersonId($about->id->value), $revision, $present),
            default => throw new \LogicException(sprintf('No item keeps a %s', $about::class)),
        };
    }
}
