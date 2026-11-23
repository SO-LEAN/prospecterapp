<?php

declare(strict_types=1);

namespace App\PersonalData\Persistence;

use App\Shared\Persistence\RevisionRead;
use App\Shared\Persistence\Table;

/**
 * A person's revision. The API exposes it with the contact, and a client sends it back with its change (ADR 0187).
 */
final readonly class PersonRevisions
{
    public function __construct(private Table $personalDataTable)
    {
    }

    public function watch(string $id): void
    {
        $this->personalDataTable->watchRevision(PersonalDataKeys::person($id), PersonalDataKeys::PERSON);
    }

    /**
     * Returns the revision the client read, or null when it sent none.
     */
    public function readAt(string $id, ?int $revision): ?RevisionRead
    {
        return null === $revision ? null : $this->personalDataTable->readAt(PersonalDataKeys::person($id), PersonalDataKeys::PERSON, $revision);
    }

    public function lastRead(string $id): int
    {
        return $this->personalDataTable->revisionRead(PersonalDataKeys::person($id), PersonalDataKeys::PERSON);
    }
}
