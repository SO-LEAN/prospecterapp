<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\RevisionRead;
use App\Shared\Persistence\Table;

/**
 * An exchange's revision. The API exposes it with the exchange, and a client sends it back with its correction (ADR
 * 0187).
 */
final readonly class ExchangeRevisions
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function watch(string $id): void
    {
        $this->prospectingTable->watchRevision(ExchangeKeys::exchange($id), ExchangeKeys::EXCHANGE);
    }

    /**
     * The revision a client read, or null when it sent none.
     */
    public function readAt(string $id, ?int $revision): ?RevisionRead
    {
        return null === $revision ? null : $this->prospectingTable->readAt(ExchangeKeys::exchange($id), ExchangeKeys::EXCHANGE, $revision);
    }

    public function lastRead(string $id): int
    {
        return $this->prospectingTable->revisionRead(ExchangeKeys::exchange($id), ExchangeKeys::EXCHANGE);
    }
}
