<?php

namespace App\Shared\Persistence;

/**
 * Which items of a partition a query reads, and in which order: below a sort key, the greatest first; above one,
 * or between two, the smallest first. A range between two keys is read by DynamoDB alone, never filtered after the
 * query.
 */
final readonly class SortKeyRange
{
    private function __construct(private ?string $lower, private ?string $upper, public bool $ascending)
    {
    }

    /**
     * Every item when no bound is given.
     */
    public static function below(?string $upper): self
    {
        return new self(null, $upper, false);
    }

    public static function above(string $lower): self
    {
        return new self($lower, null, true);
    }

    /**
     * Both bounds included, as DynamoDB reads them.
     */
    public static function between(string $lower, string $upper): self
    {
        return new self($lower, $upper, true);
    }

    /**
     * The key condition of DynamoDB on the sort key, none for every item.
     */
    public function condition(string $sortKey): ?string
    {
        return match (true) {
            null !== $this->lower && null !== $this->upper => sprintf('%s BETWEEN :lower AND :upper', $sortKey),
            null !== $this->lower => sprintf('%s > :lower', $sortKey),
            null !== $this->upper => sprintf('%s < :upper', $sortKey),
            default => null,
        };
    }

    /**
     * @return array<string, string> the values the condition names
     */
    public function values(): array
    {
        return array_filter([':lower' => $this->lower, ':upper' => $this->upper], static fn (?string $bound): bool => null !== $bound);
    }

    public function contains(string $sortKey): bool
    {
        $bothBounds = null !== $this->lower && null !== $this->upper;

        return $bothBounds
            ? strcmp($sortKey, $this->lower) >= 0 && strcmp($sortKey, $this->upper) <= 0
            : (null === $this->lower || strcmp($sortKey, $this->lower) > 0) && (null === $this->upper || strcmp($sortKey, $this->upper) < 0);
    }

    /**
     * The order of two sort keys in the range.
     */
    public function compare(string $one, string $other): int
    {
        return $this->ascending ? strcmp($one, $other) : strcmp($other, $one);
    }
}
