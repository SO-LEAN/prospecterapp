<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;

/**
 * The search of a context (ADR 0071): an aggregate carries its search keys within a scope (an account, an
 * account and a purpose) as the keys of the indexes `NAME` and `EMAIL`, left out when it has none; a search
 * reads the keys of what begins with it in both, then the aggregates found, newest first.
 */
final class SearchIndexes
{
    /**
     * The attributes the item of an aggregate carries for the indexes.
     *
     * @return array<string, string>
     */
    public static function of(string $scope, SearchKeys $keys): array
    {
        return (null === $keys->name ? [] : ['NAMEPK' => $scope, 'NAMESK' => (string) $keys->name])
            + (null === $keys->email ? [] : ['EMAILPK' => $scope, 'EMAILSK' => (string) $keys->email]);
    }

    /**
     * The partition keys of the aggregates found in the scope, newest first (UUID v7 keys descending), those
     * below the cursor, at most `max`.
     *
     * @return list<string>
     */
    public static function found(Table $table, string $scope, SearchKey $search, ?string $before, int $max): array
    {
        $keys = array_unique([
            ...$table->keysBeginningWith(Table::BY_NAME, $scope, (string) $search),
            ...$table->keysBeginningWith(Table::BY_EMAIL, $scope, (string) $search),
        ]);
        rsort($keys, SORT_STRING);

        return array_slice(array_values(array_filter($keys, static fn (string $key): bool => null === $before || $key < $before)), 0, $max);
    }
}
