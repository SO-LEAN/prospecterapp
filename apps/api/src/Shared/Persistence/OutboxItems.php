<?php

namespace App\Shared\Persistence;

/**
 * The items of outbox the tables of the contexts still keep, the week before they forget them.
 */
final readonly class OutboxItems
{
    public function __construct(private Table $accountsTable, private Table $personalDataTable, private Table $prospectingTable)
    {
    }

    /**
     * @return list<OutboxItem> those of the commits since the moment, in no particular order
     */
    public function since(\DateTimeImmutable $moment): array
    {
        return array_values(array_filter($this->all(), static fn (OutboxItem $item): bool => $item->occurredAt >= $moment));
    }

    /**
     * @return list<OutboxItem>
     */
    private function all(): array
    {
        $documents = [...$this->accountsTable->scan(OutboxItem::PARTITION), ...$this->personalDataTable->scan(OutboxItem::PARTITION), ...$this->prospectingTable->scan(OutboxItem::PARTITION)];

        return array_map(OutboxItem::fromDocument(...), $documents);
    }
}
