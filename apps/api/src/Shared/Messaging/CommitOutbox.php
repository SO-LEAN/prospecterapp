<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Identities;
use App\Shared\Persistence\OutboxItem;
use App\Shared\Persistence\Write;
use Aws\DynamoDb\Marshaler;
use Psr\Clock\ClockInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The events that the open unit of work publishes, delivered through the outbox. The outermost commit writes them
 * with what it changes, in one outbox item in the table of the use case's context. This class builds its put itself
 * from the table names, not through a `Table`: a table depends on the unit of work, which depends on the outbox.
 * When the commit publishes the same event again (the same source, type and payload), it keeps the event once. For
 * example, an aggregate's facts are published for each of its domain events (ADR 0099). Each extra event would be
 * one more reaction for the worker.
 */
final class CommitOutbox implements ResetInterface
{
    private const string NEW_ITEM = 'attribute_not_exists(PK)';

    /** @var array<string, TransportedEvent> keyed by what makes two events the same */
    private array $events = [];

    /**
     * @param array<string, string> $tables each context's table name, keyed by the context name its sources use
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
     * @return list<Write> the outbox item's put, or none when nothing was published
     *
     * @throws \LogicException when the events happened in two contexts. A transaction spans one use case.
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
