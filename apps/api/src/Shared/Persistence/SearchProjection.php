<?php

namespace App\Shared\Persistence;

use Prospecter\Core\SharedKernel\Domain\SearchTerms;

/**
 * What DynamoDB cannot do, a search by text, answered by a projection in the table of the context (ADR 0050):
 * - `TOKEN#<scope>#<beginning>` / `<subject>`: one item per beginning of a word a subject is found by
 *   (`SearchTerms::beginnings()`), within a scope (an account, an account and a purpose);
 * - `TOKENS#<subject>` / `TOKENS`: the scope and the beginnings indexed for a subject, to update or forget
 *   them without reading the subject.
 * A search looks up each of its words and keeps the subjects found by all, newest first. The projection
 * follows the domain events, and can be rebuilt from the aggregates when it drifted.
 */
final readonly class SearchProjection
{
    private const string TOKENS = 'TOKENS';

    public function __construct(private Table $table)
    {
    }

    public function index(string $subject, string $scope, SearchTerms $terms): void
    {
        $indexed = $this->table->item(self::TOKENS.'#'.$subject, self::TOKENS);
        $before = null === $indexed ? [] : self::keysOf($indexed['scope'], $indexed['beginnings'], $subject);
        $beginnings = $terms->beginnings();
        $after = self::keysOf($scope, $beginnings, $subject);

        if ($before === $after) {
            return;
        }

        $this->table->batch(
            [
                ...array_map(static fn (array $key): array => ['PK' => $key[0], 'SK' => $key[1]], array_values(array_diff_key($after, $before))),
                ['PK' => self::TOKENS.'#'.$subject, 'SK' => self::TOKENS, 'scope' => $scope, 'beginnings' => $beginnings],
            ],
            array_values(array_diff_key($before, $after)),
        );
    }

    public function forget(string $subject): void
    {
        $indexed = $this->table->item(self::TOKENS.'#'.$subject, self::TOKENS);

        if (null !== $indexed) {
            $this->table->batch([], [...array_values(self::keysOf($indexed['scope'], $indexed['beginnings'], $subject)), [self::TOKENS.'#'.$subject, self::TOKENS]]);
        }
    }

    /**
     * The subjects of the scope found by the search, newest first, below the cursor.
     *
     * @return list<string>
     */
    public function find(string $scope, SearchTerms $search, ?string $before): array
    {
        $found = null;

        foreach ($search->words as $word) {
            $subjects = array_column($this->table->query(self::tokenKey($scope, $word), before: $before), 'SK');
            $found = null === $found ? $subjects : array_values(array_intersect($found, $subjects));
        }

        $found ??= [];
        rsort($found, SORT_STRING);

        return $found;
    }

    /**
     * Forgets everything indexed, before indexing every subject again.
     */
    public function clear(): void
    {
        $this->table->batch([], array_map(static fn (array $item): array => [$item['PK'], $item['SK']], [...$this->table->scan('TOKEN#'), ...$this->table->scan(self::TOKENS.'#')]));
    }

    /**
     * @param list<string> $beginnings
     *
     * @return array<string, array{string, string}> the keys of the token items, by partition key
     */
    private static function keysOf(string $scope, array $beginnings, string $subject): array
    {
        $keys = [];

        foreach ($beginnings as $beginning) {
            $keys[self::tokenKey($scope, $beginning)] = [self::tokenKey($scope, $beginning), $subject];
        }

        return $keys;
    }

    private static function tokenKey(string $scope, string $beginning): string
    {
        return sprintf('TOKEN#%s#%s', $scope, $beginning);
    }
}
