<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKeys;
use Prospecter\Core\SharedKernel\Domain\Search\Slice;

/**
 * A slice of aggregates, the way a repository returns it (ADR 0049): the ones the search finds, newest first (by
 * descending UUID v7), after the cursor. The cursor is the id of the previous slice's last aggregate.
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

        return new Slice($slice, count($found) > $max ? $idOf(array_last($slice) ?? throw new \LogicException('A slice shorter than what was found is not empty')) : null);
    }
}
