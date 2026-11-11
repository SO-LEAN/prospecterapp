<?php

namespace App\Consistency;

use App\Reporting\Persistence\ReportingKeys;
use App\Shared\Persistence\Documents;

/**
 * The figures of each account counted again from the tables of Accounts, PersonalData and Prospecting, against those
 * Reporting keeps in its active version: its organizations, those placed on the map, its contacts, its exchanges, and
 * those of each month.
 */
final readonly class FiguresAgainstTheTables
{
    private const string ACTIVE_ORGANIZATIONS = 'activeOrganizations';
    private const string LOCATED_ORGANIZATIONS = 'locatedOrganizations';
    private const string CONTACTS = 'contacts';
    /** a month as Reporting counts it, of a moment stored in UTC */
    private const string MONTH = 'Y-m';
    private const array FIGURES_OF_THE_TOTAL = [self::ACTIVE_ORGANIZATIONS, self::LOCATED_ORGANIZATIONS, self::CONTACTS, ReportingKeys::EXCHANGES];

    public function __construct(private ContextTables $tables)
    {
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    public function discrepanciesAmong(array $accountIds): array
    {
        return array_merge(...array_map($this->discrepanciesOf(...), $accountIds));
    }

    /**
     * @return list<Discrepancy>
     */
    private function discrepanciesOf(string $accountId): array
    {
        $counted = $this->countedInTheTables($accountId);
        $reported = $this->reportedBy($accountId);
        $discrepancies = [];

        foreach (array_keys($counted + $reported) as $figure) {
            if (($counted[$figure] ?? 0) !== ($reported[$figure] ?? 0)) {
                $discrepancies[] = new Discrepancy($accountId, $figure, sprintf('Reporting counts %d, the tables %d', $reported[$figure] ?? 0, $counted[$figure] ?? 0), Correction::RebuildTheFigures);
            }
        }

        return $discrepancies;
    }

    /**
     * @return array<string, int> by figure
     */
    private function countedInTheTables(string $accountId): array
    {
        $organizations = $this->tables->organizationsOf($accountId);

        return [
            self::ACTIVE_ORGANIZATIONS => $this->tables->countOrganizationsOf($accountId),
            self::LOCATED_ORGANIZATIONS => count(array_filter($organizations, static fn (array $organization): bool => null !== $organization['geoPoint'])),
            self::CONTACTS => $this->tables->countContactsOf($accountId),
            ReportingKeys::EXCHANGES => $this->tables->countExchangesOf($accountId),
            ...$this->exchangesByMonth($accountId),
        ];
    }

    /**
     * @return array<string, int> by figure of a month
     */
    private function exchangesByMonth(string $accountId): array
    {
        $months = array_map(static fn (array $exchange): string => Documents::momentOf($exchange['facts']['occurredAt'])->format(self::MONTH), $this->tables->exchangesOf($accountId));
        $exchangesByMonth = array_count_values($months);

        return array_combine(array_map(self::figureOfTheMonth(...), array_keys($exchangesByMonth)), $exchangesByMonth);
    }

    /**
     * @return array<string, int> by figure, none before the first rebuild
     */
    private function reportedBy(string $accountId): array
    {
        $reported = [];

        foreach ($this->tables->figuresReportedFor($accountId) as $item) {
            $reported += ReportingKeys::TOTAL === $item['SK']
                ? array_map(intval(...), array_intersect_key($item, array_flip(self::FIGURES_OF_THE_TOTAL)))
                : [self::figureOfTheMonth(substr($item['SK'], strlen(ReportingKeys::MONTH))) => (int) $item[ReportingKeys::EXCHANGES]];
        }

        return $reported;
    }

    private static function figureOfTheMonth(string $month): string
    {
        return sprintf('%s of %s', ReportingKeys::EXCHANGES, $month);
    }
}
