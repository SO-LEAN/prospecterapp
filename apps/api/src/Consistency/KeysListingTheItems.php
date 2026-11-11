<?php

namespace App\Consistency;

/**
 * The keys listing each account among the accounts and each exchange in its account, by which the figures are counted
 * again: an item written before them lacks them until app:search:rebuild writes them, and nothing counts it meanwhile.
 */
final readonly class KeysListingTheItems
{
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
        return [
            ...$this->accountsOutOfTheirListAmong($accountIds),
            ...$this->exchangesOutOfTheListOfTheirAccountAmong($accountIds),
        ];
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    private function accountsOutOfTheirListAmong(array $accountIds): array
    {
        $outOfTheList = array_values(array_intersect($this->tables->accountsOutOfTheirList(), $accountIds));

        return array_map(static fn (string $accountId): Discrepancy => new Discrepancy($accountId, $accountId, 'account out of the list of the accounts', Correction::WriteTheKeysAgain), $outOfTheList);
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    private function exchangesOutOfTheListOfTheirAccountAmong(array $accountIds): array
    {
        $outOfTheList = array_filter($this->tables->exchangesOutOfTheListOfTheirAccount(), static fn (array $exchange): bool => in_array($exchange['accountId'], $accountIds, true));

        return array_values(array_map(static fn (array $exchange): Discrepancy => new Discrepancy($exchange['accountId'], $exchange['id'], 'exchange out of the list of its account', Correction::WriteTheKeysAgain), $outOfTheList));
    }
}
