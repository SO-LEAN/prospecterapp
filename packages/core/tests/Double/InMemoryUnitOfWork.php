<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * The unit of work over the InMemoryDatabase. It counts its levels like the DynamoDB one. The outermost begin()
 * takes a snapshot of the database. A discard() without its commit() abandons the unit, and the database comes
 * back once the outermost level ends. A test can make its next outermost commit refuse, as DynamoDB refuses a write
 * whose condition no longer holds (an email taken meanwhile).
 */
final class InMemoryUnitOfWork implements UnitOfWork
{
    /** @var list<bool> whether each open level is committed, the outermost first */
    private array $levels = [];
    private ?InMemoryDatabase $snapshot = null;
    private bool $abandoned = false;
    private ?BusinessException $nextRefusal = null;

    public function __construct(private readonly InMemoryDatabase $database)
    {
    }

    public function begin(): void
    {
        $this->snapshot ??= $this->database->snapshot();
        $this->levels[] = false;
    }

    /**
     * Whether a unit has begun and not ended. A command ends every unit it begins.
     */
    public function isOpen(): bool
    {
        return [] !== $this->levels;
    }

    public function refuseTheNextCommit(BusinessException $refusal): void
    {
        $this->nextRefusal = $refusal;
    }

    public function commit(): void
    {
        $level = array_key_last($this->levels) ?? throw new \LogicException('No unit of work is begun');

        if (0 === $level && $this->abandoned) {
            throw new \LogicException('The unit of work was discarded: nothing of it is written');
        }

        if (0 === $level && null !== $this->nextRefusal) {
            [$refusal, $this->nextRefusal] = [$this->nextRefusal, null];

            throw $refusal;
        }

        array_pop($this->levels);
        $this->levels[] = true;
    }

    public function discard(): void
    {
        if ([] === $this->levels) {
            return;
        }

        $committed = array_pop($this->levels);
        $this->abandoned = $this->abandoned || !$committed;

        if ([] === $this->levels) {
            $this->end();
        }
    }

    private function end(): void
    {
        if ($this->abandoned && null !== $this->snapshot) {
            $this->database->restore($this->snapshot);
        }

        $this->snapshot = null;
        $this->abandoned = false;
    }
}
