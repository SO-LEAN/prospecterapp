<?php

namespace App\Shared\Messaging;

use App\Shared\Identities;
use App\Shared\Persistence\OutboxItem;
use App\Shared\Persistence\Write;
use Aws\DynamoDb\Marshaler;
use Psr\Clock\ClockInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The events the open unit of work publishes, delivered through the outbox: written at its outermost commit, with
 * what it changes, in one item of the table of the context of the use case. It builds its put itself, by the names of
 * the tables, and not through a `Table`: a table depends on the unit of work, which depends on the outbox. An event
 * the commit publishes again, the same source, type and payload (the facts of an aggregate, published for each of
 * its domain events, ADR 0099), is kept once: each one published is a reaction more for the worker.
 */
final class CommitOutbox implements ResetInterface
{
    private const string NEW_ITEM = 'attribute_not_exists(PK)';

    /** @var array<string, TransportedEvent> by what makes it the same event */
    private array $events = [];

    /**
     * @param array<string, string> $tables the name of the table of each context, by the name its sources give it
     */
    public function __construct(
        private readonly array $tables,
        private readonly Marshaler $marshaler,
        private readonly Identities $identities,
        private readonly ClockInterface $clock,
    ) {
    }

    public function add(TransportedEvent $event): void
    {
        $this->events[self::sameness($event)] ??= $event;
    }

    private static function sameness(TransportedEvent $event): string
    {
        return json_encode([$event->source, $event->detailType, $event->payload], \JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<Write> the put of the item of outbox, none when nothing was published
     *
     * @throws \LogicException when the events happened in two contexts: a transaction spans one use case
     */
    public function writes(): array
    {
        return [] === $this->events ? [] : [$this->putOf($this->contextOfTheCommit())];
    }

    public function clear(): void
    {
        $this->events = [];
    }

    public function reset(): void
    {
        $this->clear();
    }

    private function contextOfTheCommit(): string
    {
        $contexts = array_values(array_unique(array_map(static fn (TransportedEvent $event): string => $event->context(), $this->events)));

        if (count($contexts) > 1) {
            throw new \LogicException(sprintf('A commit publishes in the context of its use case only, not in %s: what another context must do follows from the events, in its own transaction', implode(' and ', $contexts)));
        }

        return $contexts[0];
    }

    private function putOf(string $context): Write
    {
        $table = $this->tables[$context] ?? throw new \LogicException(sprintf('No table keeps the outbox of the context %s', $context));
        $item = new OutboxItem($this->identities->next(), array_values($this->events), $this->clock->now())->document();

        return new Write($table, $item['PK'], $item['SK'], ['Put' => ['TableName' => $table, 'Item' => $this->marshaler->marshalItem($item), 'ConditionExpression' => self::NEW_ITEM]]);
    }
}
