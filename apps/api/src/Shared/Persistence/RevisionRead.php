<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

/**
 * An item's revision as a client read it before sending its change. The item's save holds to this revision instead
 * of the one its command reads (ADR 0187).
 */
final readonly class RevisionRead
{
    public function __construct(public string $table, public string $pk, public string $sk, public int $revision)
    {
    }
}
