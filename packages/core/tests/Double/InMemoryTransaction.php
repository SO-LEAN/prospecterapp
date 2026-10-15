<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Entity\Person;
use Prospecter\Core\Gateway\Entity\Transaction;

/**
 * A rollback really restores the persons as they were when the transaction began.
 */
final class InMemoryTransaction implements Transaction
{
    /** @var array{array<int, Person>, int}|null */
    private ?array $snapshot = null;

    public function __construct(private readonly InMemoryDatabase $database)
    {
    }

    public function begin(): void
    {
        $this->snapshot = $this->database->snapshot();
    }

    public function commit(): void
    {
        $this->snapshot ?? throw new \LogicException('No transaction to commit');
        $this->snapshot = null;
    }

    public function rollback(): void
    {
        $this->database->restore($this->snapshot ?? throw new \LogicException('No transaction to roll back'));
        $this->snapshot = null;
    }
}
