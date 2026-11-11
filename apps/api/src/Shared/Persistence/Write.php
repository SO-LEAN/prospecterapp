<?php

namespace App\Shared\Persistence;

/**
 * One write of a transaction, a put, a delete or an addition to the numbers of one item of a table: the operation of
 * TransactWriteItems, what to throw when its condition does not hold, given the cancellation as its cause (a refusal
 * of the domain, or the delivery already applied of an item of inbox), and how it is kept after another write of its
 * item the unit is about to send: in its place, unless it says otherwise.
 */
final readonly class Write
{
    /**
     * @param array<string, mixed>                         $operation
     * @param (\Closure(ConditionFailed): \Throwable)|null $refusal
     * @param (\Closure(self): self)|null                  $afterPending what it becomes after a pending write of its item
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
     * The same item is the same table, PK and SK.
     */
    public static function itemOf(string $table, string $pk, string $sk): string
    {
        return implode("\0", [$table, $pk, $sk]);
    }

    /**
     * What the unit keeps of the item once this write follows the pending one: this write alone, unless it is
     * combined with it.
     */
    public function after(?self $pending): self
    {
        return null === $pending || null === $this->afterPending ? $this : ($this->afterPending)($pending);
    }

    /**
     * Whether the item is replaced as a whole, put or deleted, so that a read sees it as the unit is about to write
     * it; an addition leaves it read as stored.
     */
    public function replacesTheItem(): bool
    {
        return isset($this->operation['Put']) || isset($this->operation['Delete']);
    }

    /**
     * @return array<string, mixed>|null the item put, as DynamoDB keeps it; null for a delete
     */
    public function itemPut(): ?array
    {
        return $this->operation['Put']['Item'] ?? null;
    }
}
