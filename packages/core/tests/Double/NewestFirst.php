<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Slice;

/**
 * A slice of aggregates the way a repository answers (ADR 0049): found by the search, newest first (their
 * UUID v7 descending), after the cursor, which is the identity of the last aggregate of the previous slice.
 */
final class NewestFirst
{
    /**
     * @template T of object
     *
     * @param list<T>                 $aggregates
     * @param \Closure(T): string     $idOf
     * @param \Closure(T): SearchKeys $searchKeysOf
     *
     * @return Slice<T>
     */
    public static function slice(array $aggregates, \Closure $idOf, \Closure $searchKeysOf, ?SearchKey $search, ?string $after, int $max): Slice
    {
        $found = array_values(array_filter($aggregates, static fn (object $aggregate): bool => (null === $after || strcmp($idOf($aggregate), $after) < 0) && $searchKeysOf($aggregate)->areFoundBy($search)));
        usort($found, static fn (object $a, object $b): int => strcmp($idOf($b), $idOf($a)));
        $slice = array_slice($found, 0, $max);

        return new Slice($slice, count($found) > $max ? $idOf(array_last($slice)) : null);
    }
}
