<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * The unit of work over the InMemoryDatabase, its levels counted like the DynamoDB one: the outermost begin()
 * takes a snapshot of the database; a discard() without its commit() abandons the unit, and the database is
 * restored once its outermost level ends. Its next outermost commit may be refused, as DynamoDB refuses a write
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

        $this->levels[$level] = true;
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
