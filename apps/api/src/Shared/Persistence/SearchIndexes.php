<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\SearchKey;
use Prospecter\Core\SharedKernel\Domain\SearchKeys;

/**
 * What finds an aggregate of a context: its search keys (ADR 0071) and its phone number (ADR 0072), carried
 * within a scope (an account, an account and a purpose) as the keys of the indexes `NAME`, `EMAIL` and
 * `PHONE`, left out when it has none; a second key of its name, the first name of a person, on an item beside it
 * (ADR 0149). A search reads the keys of what begins with it in `NAME` and `EMAIL`,
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
        return (null === $keys->name ? [] : self::inTheNames($scope, $keys->name))
            + (null === $keys->email ? [] : ['EMAILPK' => $scope, 'EMAILSK' => (string) $keys->email])
            + (null === $phoneNumber ? [] : ['PHONEPK' => $scope, 'PHONESK' => $phoneNumber->value]);
    }

    /**
     * The attributes putting an item in the index `NAME` under a key: the name of an aggregate on its item, or the
     * first name of a person on an item beside it, of the same partition key (ADR 0149).
     *
     * @return array<string, string>
     */
    public static function inTheNames(string $scope, SearchKey $name): array
    {
        return ['NAMEPK' => $scope, 'NAMESK' => (string) $name];
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
        return self::foundIn([Table::BY_NAME, Table::BY_EMAIL], $table, $scope, $search, $before, $max);
    }

    /**
     * As `found()`, by the name alone: for a reader who does not search the emails (ADR 0129).
     *
     * @return list<string>
     */
    public static function foundByName(Table $table, string $scope, SearchKey $search, ?string $before, int $max): array
    {
        return self::foundIn([Table::BY_NAME], $table, $scope, $search, $before, $max);
    }

    /**
     * @param list<string> $indexes
     *
     * @return list<string>
     */
    private static function foundIn(array $indexes, Table $table, string $scope, SearchKey $search, ?string $before, int $max): array
    {
        $keys = array_unique(array_merge(...array_map(static fn (string $index): array => $table->keysBeginningWith($index, $scope, (string) $search), $indexes)));
        rsort($keys, SORT_STRING);

        return array_slice(array_values(array_filter($keys, static fn (string $key): bool => null === $before || $key < $before)), 0, $max);
    }
}
