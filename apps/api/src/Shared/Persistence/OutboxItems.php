<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

/**
 * The outbox items that the contexts' tables still keep, during the week before the tables forget them.
 */
final readonly class OutboxItems
{
    public function __construct(private Table $accountsTable, private Table $personalDataTable, private Table $prospectingTable)
    {
    }

    /**
     * @return list<OutboxItem> the items of the commits since the given time, in no particular order
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
