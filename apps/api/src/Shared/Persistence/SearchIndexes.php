<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;

/**
 * What finds an aggregate of a context: its search keys (ADR 0071) and its phone number (ADR 0072), carried
 * within a scope (an account, an account and a purpose) as the keys of the indexes `NAME`, `EMAIL` and
 * `PHONE`, left out when it has none. A search reads the keys of what begins with it in `NAME` and `EMAIL`,
 * a call the keys of the number in `PHONE`, then the aggregates found.
 */
final class SearchIndexes
{
    /**
     * The attributes the item of an aggregate carries for the indexes.
     *
     * @return array<string, string>
     */
    public static function of(string $scope, SearchKeys $keys, ?PhoneNumber $phoneNumber): array
    {
        return (null === $keys->name ? [] : ['NAMEPK' => $scope, 'NAMESK' => (string) $keys->name])
            + (null === $keys->email ? [] : ['EMAILPK' => $scope, 'EMAILSK' => (string) $keys->email])
            + (null === $phoneNumber ? [] : ['PHONEPK' => $scope, 'PHONESK' => $phoneNumber->value]);
    }

    /**
     * The partition keys of the aggregates of the scope having the phone number.
     *
     * @return list<string>
     */
    public static function withPhoneNumber(Table $table, string $scope, PhoneNumber $phoneNumber): array
    {
        return $table->keysAt(Table::BY_PHONE, $scope, $phoneNumber->value);
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
