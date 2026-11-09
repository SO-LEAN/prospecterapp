<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * One write of a transaction, a put or a delete of one item of a table: the operation of TransactWriteItems, and
 * the refusal to throw when its condition does not hold, given the cancellation as its cause.
 */
final readonly class Write
{
    /**
     * @param array<string, mixed>                                $operation
     * @param (\Closure(ConditionFailed): BusinessException)|null $refusal
     */
    public function __construct(public string $table, public string $pk, public string $sk, public array $operation, public ?\Closure $refusal = null)
    {
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
     * @return array<string, mixed>|null the item put, as DynamoDB keeps it; null for a delete
     */
    public function itemPut(): ?array
    {
        return $this->operation['Put']['Item'] ?? null;
    }
}
