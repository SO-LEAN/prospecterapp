<?php

namespace App\Shared\Persistence;

/**
 * Which items of a partition a query reads, and in which order: below a sort key, the greatest first, or above
 * one, the smallest first.
 */
final readonly class SortKeyRange
{
    private function __construct(public ?string $bound, public bool $ascending)
    {
    }

    /**
     * Every item when no bound is given.
     */
    public static function below(?string $bound): self
    {
        return new self($bound, false);
    }

    public static function above(string $bound): self
    {
        return new self($bound, true);
    }

    /**
     * The key condition of DynamoDB on the sort key, none for every item.
     */
    public function condition(string $sortKey, string $value): ?string
    {
        return null === $this->bound ? null : sprintf('%s %s %s', $sortKey, $this->ascending ? '>' : '<', $value);
    }

    public function contains(string $sortKey): bool
    {
        return null === $this->bound || ($this->ascending ? strcmp($sortKey, $this->bound) > 0 : strcmp($sortKey, $this->bound) < 0);
    }

    /**
     * The order of two sort keys in the range.
     */
    public function compare(string $one, string $other): int
    {
        return $this->ascending ? strcmp($one, $other) : strcmp($other, $one);
    }
}
