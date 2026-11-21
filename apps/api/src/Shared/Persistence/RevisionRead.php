<?php

namespace App\Shared\Persistence;

/**
 * The revision of an item as its client read it, before sending the change it makes on what it read: the revision
 * its save holds to instead of the one its command reads (ADR 0187).
 */
final readonly class RevisionRead
{
    public function __construct(public string $table, public string $pk, public string $sk, public int $revision)
    {
    }
}
