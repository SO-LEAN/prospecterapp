<?php

namespace App\Shared\Persistence;

use App\Shared\Messaging\CommitOutbox;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Prospecter\Core\SharedKernel\Application\Port\UnitOfWork;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * What a use case writes in the tables, kept until the outermost commit sends it in one TransactWriteItems,
 * all or nothing, with the items of outbox of what it published (CommitOutbox). While a unit is open, the writes of
 * the tables wait in it, one per item, the last one winning; without one, a write is sent at once. A write whose
 * condition does not hold throws the refusal it carries. A unit discarded at one level is abandoned until its
 * outermost level ends: what is written meanwhile, by the other reactions the event bus still calls, is thrown away
 * too, and what it published with it.
 */
final class DynamoUnitOfWork implements UnitOfWork
{
    private const int MOST_WRITES_OF_A_TRANSACTION = 100;

    /** @var list<bool> whether each open level is committed, the outermost first */
    private array $levels = [];
    /** @var array<string, Write> by item */
    private array $pending = [];
    private bool $abandoned = false;

    public function __construct(private readonly DynamoDbClient $client, private readonly CommitOutbox $outbox)
    {
    }

    public function begin(): void
    {
        $this->levels[] = false;
    }

    /**
     * @throws BusinessException the refusal a write carries, when its condition did not hold
     * @throws ConditionFailed   when the condition of a write carrying no refusal did not hold
     */
    public function commit(): void
    {
        $level = array_key_last($this->levels) ?? throw new \LogicException('No unit of work is begun');

        if (0 === $level) {
            $this->send();
        }

        $this->levels[$level] = true;
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
            $this->pending[$write->item()] = $write;
        }
    }

    /**
     * What the open unit is about to write on an item, that a read sees before it is sent.
     */
    public function pendingWriteOn(string $table, string $pk, string $sk): ?Write
    {
        return $this->pending[Write::itemOf($table, $pk, $sk)] ?? null;
    }

    /**
     * @return list<Write> what the open unit is about to write in a table
     */
    public function pendingWritesOn(string $table): array
    {
        return array_values(array_filter($this->pending, static fn (Write $write): bool => $table === $write->table));
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
