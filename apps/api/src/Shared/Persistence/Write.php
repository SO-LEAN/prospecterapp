<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\Typed;

/**
 * One write in a transaction: a put, a delete, or an addition to the numbers of one item in a table. It holds the
 * TransactWriteItems operation and what to throw when its condition does not hold, with the cancellation as the
 * cause (a domain refusal, or an inbox item whose delivery was already applied). It also says how the unit keeps it
 * after another write of the same item that the unit is about to send: in that write's place, unless it says
 * otherwise.
 *
 * @phpstan-type Operation array{Put?: array<string, mixed>, Delete?: array<string, mixed>, Update?: array<string, mixed>}
 */
final readonly class Write
{
    /**
     * @param Operation                                    $operation
     * @param (\Closure(ConditionFailed): \Throwable)|null $refusal
     * @param (\Closure(self): self)|null                  $afterPending what it becomes after a pending write of the same item
     */
    public function __construct(
        public string $table,
        public string $pk,
        public string $sk,
        public array $operation,
        public ?\Closure $refusal = null,
        private ?\Closure $afterPending = null,
    ) {
    }

    public function item(): string
    {
        return self::itemOf($this->table, $this->pk, $this->sk);
    }

    /**
     * The same item means the same table, PK and SK.
     */
    public static function itemOf(string $table, string $pk, string $sk): string
    {
        return implode("\0", [$table, $pk, $sk]);
    }

    /**
     * What the unit keeps of the item when this write follows the pending one: this write alone, unless it combines
     * with the pending one.
     */
    public function after(?self $pending): self
    {
        return null === $pending || null === $this->afterPending ? $this : ($this->afterPending)($pending);
    }

    /**
     * Whether the write replaces the item as a whole, by a put or a delete. A read then sees the item as the unit is
     * about to write it. After an addition, a read sees the item as stored.
     */
    public function replacesTheItem(): bool
    {
        return isset($this->operation['Put']) || isset($this->operation['Delete']);
    }

    /**
     * @return array<string, mixed>|null the item put, as DynamoDB keeps it, or null for a delete
     */
    public function itemPut(): ?array
    {
        return isset($this->operation['Put']) ? Typed::document($this->operation['Put']['Item'] ?? null) : null;
    }
}
