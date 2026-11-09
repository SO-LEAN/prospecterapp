<?php

namespace App\Shared\Persistence;

/**
 * The items left by the former activity journal, `JOURNAL#<subject>`, in the table of every context (ADR 0096), until
 * they are purged. A scan reads the whole table: acceptable for an operation done once.
 */
final readonly class FormerJournal
{
    private const string JOURNAL = 'JOURNAL#';

    public function __construct(private Table $accountsTable, private Table $personalDataTable, private Table $prospectingTable)
    {
    }

    /**
     * @return array<string, list<array{string, string}>> PK and SK of each item of the journal, by name of table
     */
    public function itemsByTable(): array
    {
        $items = [];

        foreach ($this->tables() as $name => $table) {
            $items[$name] = array_map(static fn (array $item): array => [$item['PK'], $item['SK']], $table->scan(self::JOURNAL));
        }

        return $items;
    }

    /**
     * @param array<string, list<array{string, string}>> $itemsByTable as itemsByTable() found them
     */
    public function delete(array $itemsByTable): void
    {
        foreach ($this->tables() as $name => $table) {
            $table->batch([], $itemsByTable[$name] ?? []);
        }
    }

    /**
     * @return array<string, Table>
     */
    private function tables(): array
    {
        return array_column([$this->accountsTable, $this->personalDataTable, $this->prospectingTable], null, 'name');
    }
}
