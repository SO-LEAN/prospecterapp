<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\RevisionRead;
use App\Shared\Persistence\Table;

/**
 * An organization's revision. The API exposes it with the organization, and a client sends it back with its change
 * (ADR 0187).
 */
final readonly class OrganizationRevisions
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function watch(string $id): void
    {
        $this->prospectingTable->watchRevision(OrganizationKeys::organization($id), OrganizationKeys::ORGANIZATION);
    }

    /**
     * The revision a client read, or null when it sent none.
     */
    public function readAt(string $id, ?int $revision): ?RevisionRead
    {
        return null === $revision ? null : $this->prospectingTable->readAt(OrganizationKeys::organization($id), OrganizationKeys::ORGANIZATION, $revision);
    }

    public function lastRead(string $id): int
    {
        return $this->prospectingTable->revisionRead(OrganizationKeys::organization($id), OrganizationKeys::ORGANIZATION);
    }
}
