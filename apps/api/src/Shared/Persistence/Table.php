<?php

namespace App\Shared\Persistence;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * The DynamoDB table of one bounded context (ADR 0038, 0048), in single-table design: every item is a plain
 * document (strings, numbers, booleans, null, lists and maps) keyed by `PK` and `SK`; the two secondary
 * indexes `GSI1` and `GSI2` list items by `GSI1PK`/`GSI1SK` and `GSI2PK`/`GSI2SK`, each repository giving
 * them their meaning; `NAME`, `EMAIL` and `PHONE` keep only the keys of what finds an aggregate (`SearchIndexes`).
 * Its writes wait in the open unit of work, sent at once without one; a read by key and the query of a partition see
 * what the unit is about to write there, a count, the keys of the search and a scan read the table as it was.
 */
final readonly class Table
{
    public const string BY_GSI1 = 'GSI1';
    public const string BY_GSI2 = 'GSI2';
    public const string BY_NAME = 'NAME';
    public const string BY_EMAIL = 'EMAIL';
    public const string BY_PHONE = 'PHONE';
    private const int BATCH_GET_SIZE = 100;
    private const int BATCH_WRITE_SIZE = 25;

    public function __construct(private DynamoDbClient $client, private Marshaler $marshaler, private DynamoUnitOfWork $unitOfWork, public string $name)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function item(string $pk, string $sk): ?array
    {
        $pending = $this->unitOfWork->pendingWriteOn($this->name, $pk, $sk);
        $item = null === $pending
            ? $this->client->getItem(['TableName' => $this->name, 'Key' => $this->key($pk, $sk), 'ConsistentRead' => true])['Item'] ?? null
            : $pending->itemPut();

        return null === $item ? null : $this->documentOf($item);
    }

    /**
     * @param list<array{string, string}> $keys PK and SK of each item
     *
     * @return list<array<string, mixed>> the items found, in no particular order
     */
    public function items(array $keys): array
    {
        $keys = array_values(array_unique($keys, SORT_REGULAR));
        $pending = array_filter(array_map(fn (array $key): ?Write => $this->unitOfWork->pendingWriteOn($this->name, ...$key), $keys));
        $put = array_filter(array_map(static fn (Write $write): ?array => $write->itemPut(), $pending));

        return [...array_map($this->documentOf(...), array_values($put)), ...$this->storedItems(array_values(array_diff_key($keys, $pending)))];
    }

    /**
     * @param list<array{string, string}> $keys
     *
     * @return list<array<string, mixed>>
     */
    private function storedItems(array $keys): array
    {
        $found = [];

        foreach (array_chunk($keys, self::BATCH_GET_SIZE) as $chunk) {
            $request = [$this->name => ['Keys' => array_map(fn (array $key): array => $this->key(...$key), $chunk), 'ConsistentRead' => true]];

            while ([] !== $request) {
                $result = $this->client->batchGetItem(['RequestItems' => $request]);
                $found = [...$found, ...array_map($this->documentOf(...), $result['Responses'][$this->name] ?? [])];
                $request = $result['UnprocessedKeys'] ?? [];
            }
        }

        return $found;
    }

    /**
     * The items of a partition, by sort key descending (the newest first for UUID v7 keys), below `before`
     * when given, at most `limit`.
     *
     * @return list<array<string, mixed>>
     */
    public function query(string $partition, ?string $index = null, ?string $before = null, ?int $limit = null): array
    {
        return $this->queryIn(SortKeyRange::below($before), $partition, $index, $limit);
    }

    /**
     * The items of a partition, by sort key ascending (the soonest first for keys beginning with a moment),
     * above `after`, at most `limit`.
     *
     * @return list<array<string, mixed>>
     */
    public function queryAfter(string $partition, ?string $index, string $after, ?int $limit = null): array
    {
        return $this->queryIn(SortKeyRange::above($after), $partition, $index, $limit);
    }

    /**
     * The items of a partition, by sort key ascending, from `lower` to `upper`, both included.
     *
     * @return list<array<string, mixed>>
     */
    public function queryBetween(string $partition, ?string $index, string $lower, string $upper): array
    {
        return $this->queryIn(SortKeyRange::between($lower, $upper), $partition, $index, null);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function queryIn(SortKeyRange $range, string $partition, ?string $index, ?int $limit): array
    {
        $pending = $this->unitOfWork->pendingWritesOn($this->name);

        if ([] === $pending) {
            return $this->storedItemsOf($partition, $index, $range, $limit);
        }

        $stored = $this->storedItemsOf($partition, $index, $range, null === $limit ? null : $limit + count($pending));

        return array_slice($this->withPending($pending, $stored, $partition, $index, $range), 0, $limit);
    }

    /**
     * The items of a partition as the open unit is about to write them, in the order of the range.
     *
     * @param list<Write>                $pending
     * @param list<array<string, mixed>> $stored
     *
     * @return list<array<string, mixed>>
     */
    private function withPending(array $pending, array $stored, string $partition, ?string $index, SortKeyRange $range): array
    {
        $sortKey = self::keyNamesOf($index)[1];
        $items = [...$this->notRewritten($stored, $pending), ...$this->putIn($pending, $partition, $index, $range)];
        usort($items, static fn (array $one, array $other): int => $range->compare($one[$sortKey], $other[$sortKey]));

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $stored
     * @param list<Write>                $pending
     *
     * @return list<array<string, mixed>> the items stored that no pending write changes
     */
    private function notRewritten(array $stored, array $pending): array
    {
        $rewritten = array_flip(array_map(static fn (Write $write): string => $write->item(), $pending));

        return array_values(array_filter($stored, fn (array $item): bool => !isset($rewritten[Write::itemOf($this->name, $item['PK'], $item['SK'])])));
    }

    /**
     * @param list<Write> $pending
     *
     * @return list<array<string, mixed>> the items put in the partition, within the range
     */
    private function putIn(array $pending, string $partition, ?string $index, SortKeyRange $range): array
    {
        [$partitionKey, $sortKey] = self::keyNamesOf($index);
        $put = array_map($this->documentOf(...), array_values(array_filter(array_map(static fn (Write $write): ?array => $write->itemPut(), $pending))));

        return array_values(array_filter($put, static fn (array $item): bool => $partition === ($item[$partitionKey] ?? null) && isset($item[$sortKey]) && $range->contains($item[$sortKey])));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function storedItemsOf(string $partition, ?string $index, SortKeyRange $range, ?int $limit): array
    {
        [$pkName, $skName] = self::keyNamesOf($index);
        $condition = $range->condition('#sk');
        $request = [
            'TableName' => $this->name,
            'KeyConditionExpression' => null === $condition ? '#pk = :pk' : '#pk = :pk AND '.$condition,
            'ExpressionAttributeNames' => ['#pk' => $pkName] + (null === $condition ? [] : ['#sk' => $skName]),
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':pk' => $partition] + $range->values()),
            'ScanIndexForward' => $range->ascending,
        ] + (null === $index ? ['ConsistentRead' => true] : ['IndexName' => $index]);
        $found = [];

        do {
            $result = $this->client->query($request + (null === $limit ? [] : ['Limit' => $limit - count($found)]));
            $found = [...$found, ...array_map($this->documentOf(...), $result['Items'] ?? [])];
            $request['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
        } while (null !== $request['ExclusiveStartKey'] && (null === $limit || count($found) < $limit));

        return $found;
    }

    /**
     * @return array{string, string} the names of the partition key and of the sort key of the table or of the index
     */
    private static function keyNamesOf(?string $index): array
    {
        return null === $index ? ['PK', 'SK'] : [$index.'PK', $index.'SK'];
    }

    /**
     * How many items an index lists in a partition, counted by DynamoDB without reading them.
     */
    public function count(string $partition, string $index): int
    {
        $request = [
            'TableName' => $this->name,
            'IndexName' => $index,
            'KeyConditionExpression' => '#pk = :pk',
            'ExpressionAttributeNames' => ['#pk' => $index.'PK'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':pk' => $partition]),
            'Select' => 'COUNT',
        ];
        $count = 0;

        do {
            $result = $this->client->query($request);
            $count += (int) $result['Count'];
            $request['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
        } while (null !== $request['ExclusiveStartKey']);

        return $count;
    }

    /**
     * The partition keys of the items an index lists in a partition with a sort key beginning with
     * `beginning`, every one of them.
     *
     * @return list<string>
     */
    public function keysBeginningWith(string $index, string $partition, string $beginning): array
    {
        return $this->keysWhere($index, $partition, 'begins_with(#sk, :sk)', $beginning);
    }

    /**
     * The partition keys of the items an index lists in a partition with exactly this sort key.
     *
     * @return list<string>
     */
    public function keysAt(string $index, string $partition, string $sortKey): array
    {
        return $this->keysWhere($index, $partition, '#sk = :sk', $sortKey);
    }

    /**
     * Every item whose partition key begins with the prefix.
     *
     * @return list<array<string, mixed>>
     */
    public function scan(string $partitionPrefix): array
    {
        $request = [
            'TableName' => $this->name,
            'FilterExpression' => 'begins_with(#pk, :prefix)',
            'ExpressionAttributeNames' => ['#pk' => 'PK'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':prefix' => $partitionPrefix]),
            'ConsistentRead' => true,
        ];
        $found = [];

        do {
            $result = $this->client->scan($request);
            $found = [...$found, ...array_map($this->documentOf(...), $result['Items'] ?? [])];
            $request['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
        } while (null !== $request['ExclusiveStartKey']);

        return $found;
    }

    /**
     * A put of a transaction, written only when the condition holds, the refusal thrown otherwise.
     *
     * @param array{PK: string, SK: string, ...}                  $item
     * @param array<string, mixed>                                $values  the values the condition names
     * @param (\Closure(ConditionFailed): BusinessException)|null $refusal
     */
    public function putting(array $item, ?string $condition = null, array $values = [], ?\Closure $refusal = null): Write
    {
        return new Write($this->name, $item['PK'], $item['SK'], ['Put' => ['TableName' => $this->name, 'Item' => $this->marshaler->marshalItem($item)] + $this->conditionOf($condition, $values)], $refusal);
    }

    /**
     * A put of a transaction whose condition is about the item as stored: put again in the same unit, it keeps the
     * condition of the first put, the item as stored being the same.
     *
     * @param array{PK: string, SK: string, ...} $item
     * @param array<string, mixed>               $values the values the condition names
     */
    public function replacing(array $item, string $condition, array $values = []): Write
    {
        return $this->replacingUnder($item, $this->conditionOf($condition, $values));
    }

    /**
     * @param array{PK: string, SK: string, ...} $item
     * @param array<string, mixed>               $condition the condition of the put, as DynamoDB takes it
     */
    private function replacingUnder(array $item, array $condition): Write
    {
        return new Write(
            $this->name,
            $item['PK'],
            $item['SK'],
            ['Put' => ['TableName' => $this->name, 'Item' => $this->marshaler->marshalItem($item)] + $condition],
            afterPending: fn (Write $pending): Write => $this->replacingUnder($item, self::conditionOfTheOperation($pending)),
        );
    }

    /**
     * @return array<string, mixed> the condition of a write, as DynamoDB takes it
     */
    private static function conditionOfTheOperation(Write $write): array
    {
        $operation = $write->operation['Put'] ?? $write->operation['Delete'] ?? [];

        return array_intersect_key($operation, array_flip(['ConditionExpression', 'ExpressionAttributeValues']));
    }

    /**
     * An addition to numbers of an item, created with them when it is not there: what a transaction adds is added to
     * what another one added meanwhile, and two additions to the item in the same unit are one, their sum.
     *
     * @param non-empty-array<string, int> $increments by the name of the number
     */
    public function adding(string $pk, string $sk, array $increments): Write
    {
        $names = array_keys($increments);
        $placeholders = array_map(static fn (int $position): string => 'n'.$position, array_keys($names));

        return new Write($this->name, $pk, $sk, ['Update' => [
            'TableName' => $this->name,
            'Key' => $this->key($pk, $sk),
            'UpdateExpression' => 'ADD '.implode(', ', array_map(static fn (string $placeholder): string => sprintf('#%1$s :%1$s', $placeholder), $placeholders)),
            'ExpressionAttributeNames' => array_combine(array_map(static fn (string $placeholder): string => '#'.$placeholder, $placeholders), $names),
            'ExpressionAttributeValues' => $this->marshaler->marshalItem(array_combine(array_map(static fn (string $placeholder): string => ':'.$placeholder, $placeholders), array_values($increments))),
        ]], afterPending: fn (Write $pending): Write => $this->adding($pk, $sk, self::sum($this->incrementsOf($pending), $increments)));
    }

    /**
     * @return array<string, int> what an addition adds, by the name of the number
     */
    private function incrementsOf(Write $addition): array
    {
        $update = $addition->operation['Update'] ?? throw new \LogicException(sprintf('Only an addition is followed by an addition to %s %s', $addition->pk, $addition->sk));
        $values = $this->marshaler->unmarshalItem($update['ExpressionAttributeValues']);
        $increments = [];

        foreach ($update['ExpressionAttributeNames'] as $placeholder => $name) {
            $increments[$name] = (int) $values[':'.substr($placeholder, 1)];
        }

        return $increments;
    }

    /**
     * @param array<string, int> $increments
     * @param array<string, int> $others
     *
     * @return non-empty-array<string, int>
     */
    private static function sum(array $increments, array $others): array
    {
        $sum = $increments;

        foreach ($others as $name => $increment) {
            $sum[$name] = ($sum[$name] ?? 0) + $increment;
        }

        return $sum;
    }

    /**
     * A delete of a transaction.
     */
    public function deleting(string $pk, string $sk): Write
    {
        return new Write($this->name, $pk, $sk, ['Delete' => ['TableName' => $this->name, 'Key' => $this->key($pk, $sk)]]);
    }

    /**
     * Writes all or nothing, with what the open unit of work writes.
     *
     * @param list<Write> $writes made by putting(), replacing(), adding() and deleting()
     *
     * @throws ConditionFailed when the condition of a write carrying no refusal did not hold
     */
    public function transact(array $writes): void
    {
        $this->unitOfWork->write(...$writes);
    }

    /**
     * Writes many items independently, for a projection that can be rebuilt.
     *
     * @param list<array<string, mixed>>  $puts
     * @param list<array{string, string}> $deletes PK and SK of each item
     */
    public function batch(array $puts, array $deletes = []): void
    {
        $requests = [
            ...array_map(fn (array $item): array => ['PutRequest' => ['Item' => $this->marshaler->marshalItem($item)]], $puts),
            ...array_map(fn (array $key): array => ['DeleteRequest' => ['Key' => $this->key(...$key)]], $deletes),
        ];

        foreach (array_chunk($requests, self::BATCH_WRITE_SIZE) as $chunk) {
            $pending = [$this->name => $chunk];

            while ([] !== $pending) {
                $pending = $this->client->batchWriteItem(['RequestItems' => $pending])['UnprocessedItems'] ?? [];
            }
        }
    }

    /**
     * @return list<string>
     */
    private function keysWhere(string $index, string $partition, string $sortKeyCondition, string $sortKey): array
    {
        $request = [
            'TableName' => $this->name,
            'IndexName' => $index,
            'KeyConditionExpression' => '#pk = :pk AND '.$sortKeyCondition,
            'ExpressionAttributeNames' => ['#pk' => $index.'PK', '#sk' => $index.'SK'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':pk' => $partition, ':sk' => $sortKey]),
        ];
        $keys = [];

        do {
            $result = $this->client->query($request);
            $keys = [...$keys, ...array_map(fn (array $item): string => (string) $this->documentOf($item)['PK'], $result['Items'] ?? [])];
            $request['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
        } while (null !== $request['ExclusiveStartKey']);

        return $keys;
    }

    /**
     * @return array<string, mixed>
     */
    private function key(string $pk, string $sk): array
    {
        return $this->marshaler->marshalItem(['PK' => $pk, 'SK' => $sk]);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function conditionOf(?string $condition, array $values): array
    {
        if (null === $condition) {
            return [];
        }

        return ['ConditionExpression' => $condition] + ([] === $values ? [] : ['ExpressionAttributeValues' => $this->marshaler->marshalItem($values)]);
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array<string, mixed>
     */
    private function documentOf(array $item): array
    {
        return $this->marshaler->unmarshalItem($item);
    }
}
