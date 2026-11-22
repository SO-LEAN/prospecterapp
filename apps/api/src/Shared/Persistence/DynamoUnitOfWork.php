<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\Messaging\AlreadyApplied;
use App\Shared\Messaging\CommitOutbox;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * What a use case writes in the tables, kept until the outermost commit sends it in one TransactWriteItems,
 * all or nothing, with the items of outbox of what it published (CommitOutbox). While a unit is open, the writes of
 * the tables wait in it, one per item, the last one winning unless it combines with the one before (Write::after());
 * without one, a write is sent at once. A write whose
 * condition does not hold throws the refusal it carries. A unit discarded at one level is abandoned until its
 * outermost level ends: what is written meanwhile, by the other reactions the event bus still calls, is thrown away
 * too, and what it published with it. It remembers each item it first read by key, for the lock of its revision
 * (ADR 0181), until its outermost level ends; held to the revision a client read of an item, it locks that one
 * instead (ADR 0187).
 */
final class DynamoUnitOfWork implements UnitOfWork
{
    private const int MOST_WRITES_OF_A_TRANSACTION = 100;

    /** @var list<bool> whether each open level is committed, the outermost first */
    private array $levels = [];
    /** @var array<string, Write> by item */
    private array $pending = [];
    private bool $abandoned = false;
    /** @var array<string, RevisionLock> by item, the lock of the revision the unit first read */
    private array $locks = [];
    private ?RevisionRead $heldTo = null;

    public function __construct(private readonly DynamoDbClient $client, private readonly CommitOutbox $outbox)
    {
    }

    public function begin(): void
    {
        $this->levels[] = false;
    }

    /**
     * @throws BusinessException the refusal a write carries, when its condition did not hold
     * @throws AlreadyApplied    when the item of inbox it writes is there already: the event was applied
     * @throws ConditionFailed   when the condition of a write carrying no refusal did not hold
     */
    public function commit(): void
    {
        $level = array_key_last($this->levels) ?? throw new \LogicException('No unit of work is begun');

        if (0 === $level) {
            $this->send();
        }

        array_pop($this->levels);
        $this->levels[] = true;
    }

    public function discard(): void
    {
        if ([] === $this->levels) {
            return;
        }

        if (!array_pop($this->levels)) {
            $this->abandon();
        }

        if ([] === $this->levels) {
            $this->abandoned = false;
            $this->locks = [];
            $this->outbox->clear();
        }
    }

    /**
     * Outside a unit, what is published is a commit of its own, its outbox written at once.
     */
    public function sendTheOutboxOutsideAUnit(): void
    {
        if ([] === $this->levels) {
            $this->write();
        }
    }

    /**
     * Keeps the writes in the open unit, or sends them at once when none is.
     */
    public function write(Write ...$writes): void
    {
        $this->begin();

        try {
            $this->keep(...$writes);
            $this->commit();
        } finally {
            $this->discard();
        }
    }

    private function keep(Write ...$writes): void
    {
        if ($this->abandoned) {
            return;
        }

        foreach ($writes as $write) {
            $this->pending[$write->item()] = $write->after($this->pending[$write->item()] ?? null);
        }
    }

    /**
     * What the open unit is about to put or delete on an item, that a read sees before it is sent.
     */
    public function pendingWriteOn(string $table, string $pk, string $sk): ?Write
    {
        $pending = $this->pending[Write::itemOf($table, $pk, $sk)] ?? null;

        return true === $pending?->replacesTheItem() ? $pending : null;
    }

    /**
     * Holds the save of an item to the revision its client read, until released: the first read of that item by a
     * unit locks this revision instead of the one it reads (ADR 0187).
     */
    public function holdTo(RevisionRead $read): void
    {
        $this->heldTo = $read;
    }

    public function release(): void
    {
        $this->heldTo = null;
    }

    /**
     * Notes, while a unit is open, an item read from its table, the first read winning: what the lock of its revision
     * holds to (ADR 0181).
     *
     * @param array<string, mixed>|null $item null when it is absent
     */
    public function noteRead(string $table, string $pk, string $sk, ?array $item): void
    {
        if ([] === $this->levels) {
            return;
        }

        $this->locks[Write::itemOf($table, $pk, $sk)] ??= $this->isHeld($table, $pk, $sk) ? RevisionLock::at($this->heldTo->revision) : RevisionLock::on($item);
    }

    /**
     * @phpstan-assert-if-true !null $this->heldTo
     */
    private function isHeld(string $table, string $pk, string $sk): bool
    {
        return null !== $this->heldTo && Write::itemOf($table, $pk, $sk) === Write::itemOf($this->heldTo->table, $this->heldTo->pk, $this->heldTo->sk);
    }

    /**
     * The lock on the revision of an item the open unit read, null when it read none.
     */
    public function lockOn(string $table, string $pk, string $sk): ?RevisionLock
    {
        return $this->locks[Write::itemOf($table, $pk, $sk)] ?? null;
    }

    /**
     * @return list<Write> what the open unit is about to put or delete in a table
     */
    public function pendingWritesOn(string $table): array
    {
        return array_values(array_filter($this->pending, static fn (Write $write): bool => $table === $write->table && $write->replacesTheItem()));
    }

    private function send(): void
    {
        if ($this->abandoned) {
            throw new \LogicException('The unit of work was discarded: nothing of it is written');
        }

        $writes = [...array_values($this->pending), ...$this->outbox->writes()];
        $this->pending = [];

        if ([] === $writes) {
            return;
        }

        if (count($writes) > self::MOST_WRITES_OF_A_TRANSACTION) {
            throw new \LogicException(sprintf('A unit of work writes at most %d items at once, not %d', self::MOST_WRITES_OF_A_TRANSACTION, count($writes)));
        }

        try {
            $this->client->transactWriteItems(['TransactItems' => array_map(static fn (Write $write): array => $write->operation, $writes)]);
        } catch (DynamoDbException $failure) {
            throw 'TransactionCanceledException' === $failure->getAwsErrorCode() ? self::refusalOf(ConditionFailed::in($failure), $writes) : $failure;
        }
    }

    /**
     * @param list<Write> $writes
     */
    private static function refusalOf(ConditionFailed $cancelled, array $writes): \Throwable
    {
        $refusal = array_find(array_map(static fn (int $position): ?\Closure => $writes[$position]->refusal, $cancelled->failedWrites), static fn (?\Closure $refusal): bool => null !== $refusal);

        return null === $refusal ? $cancelled : $refusal($cancelled);
    }

    private function abandon(): void
    {
        $this->abandoned = true;
        $this->pending = [];
    }
}
