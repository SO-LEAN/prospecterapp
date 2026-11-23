<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKey;
use Prospecter\Core\SharedKernel\Domain\Search\SearchKeys;

/**
 * What finds a context's aggregate: its search keys (ADR 0071) and its phone number (ADR 0072). They are carried
 * within a scope (an account, or an account and a purpose) as the keys of the `NAME`, `EMAIL` and `PHONE` indexes,
 * and left out when the aggregate has none. A second key for the name, a person's first name, goes on an item beside
 * it (ADR 0149). A search reads the keys that begin with it in `NAME` and `EMAIL`. A call reads the number's keys in
 * `PHONE`. Then both read the aggregates found.
 */
final class SearchIndexes
{
    /**
     * The attributes that an aggregate's item carries for the indexes.
     *
     * @return array{NAMEPK?: string, NAMESK?: string, EMAILPK?: string, EMAILSK?: string, PHONEPK?: string, PHONESK?: string}
     */
    public static function of(string $scope, SearchKeys $keys, ?PhoneNumber $phoneNumber): array
    {
        return (null === $keys->name ? [] : self::inTheNames($scope, $keys->name))
            + (null === $keys->email ? [] : ['EMAILPK' => $scope, 'EMAILSK' => (string) $keys->email])
            + (null === $phoneNumber ? [] : ['PHONEPK' => $scope, 'PHONESK' => $phoneNumber->value]);
    }

    /**
     * The attributes that put an item in the `NAME` index under a key. The key is an aggregate's name on its item, or a
     * person's first name on an item beside it, with the same partition key (ADR 0149).
     *
     * @return array{NAMEPK: string, NAMESK: string}
     */
    public static function inTheNames(string $scope, SearchKey $name): array
    {
        return ['NAMEPK' => $scope, 'NAMESK' => (string) $name];
    }

    /**
     * The partition keys of the scope's aggregates that have the phone number.
     *
     * @return list<string>
     */
    public static function withPhoneNumber(Table $table, string $scope, PhoneNumber $phoneNumber): array
    {
        return $table->keysAt(Table::BY_PHONE, $scope, $phoneNumber->value);
    }

    /**
     * The partition keys of the aggregates found in the scope, newest first (UUID v7 keys descending). It returns those
     * below the cursor, at most `max`.
     *
     * @return list<string>
     */
    public static function found(Table $table, string $scope, SearchKey $search, ?string $before, int $max): array
    {
        return self::foundIn([Table::BY_NAME, Table::BY_EMAIL], $table, $scope, $search, $before, $max);
    }

    /**
     * Like `found()`, by the name alone: for a reader who does not search the emails (ADR 0129).
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
