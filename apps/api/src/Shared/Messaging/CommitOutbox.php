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
 * what it changes, in one item per table of the contexts they happened in.
 */
final class CommitOutbox implements ResetInterface
{
    private const string NEW_ITEM = 'attribute_not_exists(PK)';

    /** @var list<TransportedEvent> */
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
        $this->events[] = $event;
    }

    /**
     * @return list<Write> the puts of the items of outbox, none when nothing was published
     */
    public function writes(): array
    {
        $byContext = [];

        foreach ($this->events as $event) {
            $byContext[$event->context()][] = $event;
        }

        return array_map($this->putOf(...), array_keys($byContext), array_values($byContext));
    }

    public function clear(): void
    {
        $this->events = [];
    }

    public function reset(): void
    {
        $this->clear();
    }

    /**
     * @param list<TransportedEvent> $events
     */
    private function putOf(string $context, array $events): Write
    {
        $table = $this->tables[$context] ?? throw new \LogicException(sprintf('No table keeps the outbox of the context %s', $context));
        $item = new OutboxItem($this->identities->next(), $events, $this->clock->now())->document();

        return new Write($table, $item['PK'], $item['SK'], ['Put' => ['TableName' => $table, 'Item' => $this->marshaler->marshalItem($item), 'ConditionExpression' => self::NEW_ITEM]]);
    }
}
