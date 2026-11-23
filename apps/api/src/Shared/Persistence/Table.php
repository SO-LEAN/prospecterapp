<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use App\Shared\Typed;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\ResultInterface;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessException;

/**
 * One bounded context's DynamoDB table (ADR 0038, 0048), in single-table design. Every item is a plain document
 * (strings, numbers, booleans, null, lists and maps) keyed by `PK` and `SK`. The two secondary indexes, `GSI1` and
 * `GSI2`, list items by `GSI1PK`/`GSI1SK` and `GSI2PK`/`GSI2SK`. Each repository gives them their meaning. `NAME`,
 * `EMAIL` and `PHONE` keep only the keys of what finds an aggregate (`SearchIndexes`).
 *
 * The table's writes wait in the open unit of work, or are sent at once when no unit is open. A read by key and a
 * partition query see what the unit is about to write there. A count, the search keys and a scan read the table as
 * it was.
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

    public function __construct(private DynamoDbClient $client, private Marshaler $marshaler, private DynamoUnitOfWork $unitOfWork, private RevisionsRead $revisionsRead, public string $name)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function item(string $pk, string $sk): ?array
    {
        $pending = $this->unitOfWork->pendingWriteOn($this->name, $pk, $sk);

        if (null !== $pending) {
            $put = $pending->itemPut();

            return null === $put ? null : $this->documentOf($put);
        }

        return $this->storedItem($pk, $sk);
    }

    /**
     * @return array<string, mixed>|null the item as the table keeps it, with its revision recorded by the open unit
     */
    private function storedItem(string $pk, string $sk): ?array
    {
        $stored = $this->client->getItem(['TableName' => $this->name, 'Key' => self::key($pk, $sk), 'ConsistentRead' => true])['Item'] ?? null;
        $item = null === $stored ? null : $this->documentOf($stored);
        $this->noteRead([[$pk, $sk]], null === $item ? [] : [$item]);

        return $item;
    }

    /**
     * Keeps the revision of an item's next reads, to expose it with what is read from the item (ADR 0187).
     */
    public function watchRevision(string $pk, string $sk): void
    {
        $this->revisionsRead->watch($this->name, $pk, $sk);
    }

    /**
     * The revision at a watched item's last read.
     */
    public function revisionRead(string $pk, string $sk): int
    {
        return $this->revisionsRead->of($this->name, $pk, $sk);
    }

    /**
     * The revision of a table item that its client read. The save of what the client read holds to it (ADR 0187).
     */
    public function readAt(string $pk, string $sk, int $revision): RevisionRead
    {
        return new RevisionRead($this->name, $pk, $sk, $revision);
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
     * Reads the items under one sort key in the given partitions. It returns them in the partitions' order and leaves
     * out a partition without one.
     *
     * @param list<string> $partitions
     *
     * @return list<array<string, mixed>>
     */
    public function itemsInTheOrderOf(array $partitions, string $sortKey): array
    {
        $byPartition = [];

        foreach ($this->items(array_map(static fn (string $partition): array => [$partition, $sortKey], $partitions)) as $item) {
            $byPartition[self::pkAndSkOf($item)[0]] = $item;
        }

        return array_values(array_filter(array_map(static fn (string $partition): ?array => $byPartition[$partition] ?? null, $partitions)));
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
            while ([] !== $chunk) {
                $result = $this->client->batchGetItem(['RequestItems' => [$this->name => ['Keys' => array_map(static fn (array $key): array => self::key(...$key), $chunk), 'ConsistentRead' => true]]]);
                $found = [...$found, ...$this->documentsOf(Typed::document($result['Responses'] ?? [])[$this->name] ?? [])];
                $chunk = $this->unprocessedKeysOf($result);
            }
        }

        $this->noteRead($keys, $found);

        return $found;
    }

    /**
     * @return list<array{string, string}> PK and SK of each item that the batch left to read again
     */
    private function unprocessedKeysOf(ResultInterface $result): array
    {
        $unprocessed = Typed::document($result['UnprocessedKeys'] ?? [])[$this->name] ?? null;

        return null === $unprocessed ? [] : array_map(self::pkAndSkOf(...), $this->documentsOf(Typed::document($unprocessed)['Keys'] ?? []));
    }

    /**
     * @param array<string, mixed> $item
     *
     * @return array{string, string}
     */
    private static function pkAndSkOf(array $item): array
    {
        return [Typed::string($item['PK'] ?? null), Typed::string($item['SK'] ?? null)];
    }

    /**
     * Records in the open unit the items read by key from the table, the absent ones too, and the watched revisions.
     *
     * @param list<array{string, string}> $keys
     * @param list<array<string, mixed>>  $found
     */
    private function noteRead(array $keys, array $found): void
    {
        $byItem = [];

        foreach ($found as $item) {
            $byItem[Write::itemOf($this->name, ...self::pkAndSkOf($item))] = $item;
        }

        foreach ($keys as [$pk, $sk]) {
            $item = $byItem[Write::itemOf($this->name, $pk, $sk)] ?? null;
            $this->unitOfWork->noteRead($this->name, $pk, $sk, $item);
            $this->revisionsRead->note($this->name, $pk, $sk, $item);
        }
    }

    /**
     * A partition's items by sort key, descending (the newest first for UUID v7 keys): those below `before` when
     * given, at most `limit`.
     *
     * @return list<array<string, mixed>>
     */
    public function query(string $partition, ?string $index = null, ?string $before = null, ?int $limit = null): array
    {
        return $this->queryIn(SortKeyRange::below($before), $partition, $index, $limit);
    }

    /**
     * A partition's items by sort key, ascending (the soonest first for keys that begin with a time): those above
     * `after`, at most `limit`.
     *
     * @return list<array<string, mixed>>
     */
    public function queryAfter(string $partition, ?string $index, string $after, ?int $limit = null): array
    {
        return $this->queryIn(SortKeyRange::above($after), $partition, $index, $limit);
    }

    /**
     * A partition's items by sort key, ascending, from `lower` to `upper`, both included.
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
     * A partition's items as the open unit is about to write them, in the range's order.
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
        usort($items, static fn (array $one, array $other): int => $range->compare(Typed::string($one[$sortKey] ?? null), Typed::string($other[$sortKey] ?? null)));

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $stored
     * @param list<Write>                $pending
     *
     * @return list<array<string, mixed>> the stored items that no pending write changes
     */
    private function notRewritten(array $stored, array $pending): array
    {
        $rewritten = array_flip(array_map(static fn (Write $write): string => $write->item(), $pending));

        return array_values(array_filter($stored, fn (array $item): bool => !isset($rewritten[Write::itemOf($this->name, ...self::pkAndSkOf($item))])));
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

        return array_values(array_filter($put, static fn (array $item): bool => $partition === ($item[$partitionKey] ?? null) && isset($item[$sortKey]) && $range->contains(Typed::string($item[$sortKey]))));
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
            'ExpressionAttributeValues' => self::strings([':pk' => $partition] + $range->values()),
            'ScanIndexForward' => $range->ascending,
        ] + (null === $index ? ['ConsistentRead' => true] : ['IndexName' => $index]);
        $found = [];
        $last = null;

        do {
            $result = $this->client->query($request + self::startingAfter($last) + (null === $limit ? [] : ['Limit' => $limit - count($found)]));
            $found = [...$found, ...$this->documentsOf($result['Items'] ?? [])];
            $last = $this->lastKeyOf($result);
        } while (null !== $last && (null === $limit || count($found) < $limit));

        return $found;
    }

    /**
     * @return array{string, string} the names of the table's or the index's partition key and sort key
     */
    private static function keyNamesOf(?string $index): array
    {
        return null === $index ? ['PK', 'SK'] : [$index.'PK', $index.'SK'];
    }

    /**
     * How many items an index lists in a partition. DynamoDB counts them without reading them.
     */
    public function count(string $partition, string $index): int
    {
        $request = [
            'TableName' => $this->name,
            'IndexName' => $index,
            'KeyConditionExpression' => '#pk = :pk',
            'ExpressionAttributeNames' => ['#pk' => $index.'PK'],
            'ExpressionAttributeValues' => self::strings([':pk' => $partition]),
            'Select' => 'COUNT',
        ];
        $count = 0;
        $last = null;

        do {
            $result = $this->client->query($request + self::startingAfter($last));
            $count += Typed::int($result['Count']);
            $last = $this->lastKeyOf($result);
        } while (null !== $last);

        return $count;
    }

    /**
     * The partition keys of all the items that an index lists in a partition with a sort key beginning with
     * `beginning`.
     *
     * @return list<string>
     */
    public function keysBeginningWith(string $index, string $partition, string $beginning): array
    {
        return $this->keysWhere($index, $partition, 'begins_with(#sk, :sk)', $beginning);
    }

    /**
     * The partition keys of the items that an index lists in a partition with exactly this sort key.
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
            'ExpressionAttributeValues' => self::strings([':prefix' => $partitionPrefix]),
            'ConsistentRead' => true,
        ];
        $found = [];
        $last = null;

        do {
            $result = $this->client->scan($request + self::startingAfter($last));
            $found = [...$found, ...$this->documentsOf($result['Items'] ?? [])];
            $last = $this->lastKeyOf($result);
        } while (null !== $last);

        return $found;
    }

    /**
     * A put in a transaction. It is written only when the condition holds, and the refusal is thrown otherwise.
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
     * The put of an aggregate, under an optimistic lock on its revision (ADR 0181).
     *
     * @param array{PK: string, SK: string, ...} $item
     */
    public function revising(array $item): Write
    {
        $lock = $this->unitOfWork->lockOn($this->name, $item['PK'], $item['SK']);

        return null === $lock ? $this->putting($item) : $this->putting($item, $lock->condition, $lock->values);
    }

    /**
     * A put in a transaction whose condition is about the item as stored. When the same unit puts the item again, the
     * new put keeps the first put's condition, since the stored item is the same.
     *
     * @param array{PK: string, SK: string, ...} $item
     * @param array<string, mixed>               $values the values the condition names
     * @param array<string, string>              $names  the attributes that the condition names by a placeholder, such as a reserved word
     */
    public function replacing(array $item, string $condition, array $values = [], array $names = []): Write
    {
        return $this->replacingUnder($item, $this->conditionOf($condition, $values, $names));
    }

    /**
     * @param array{PK: string, SK: string, ...} $item
     * @param array<string, mixed>               $condition the put's condition, as DynamoDB takes it
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
     * @return array<string, mixed> a write's condition, as DynamoDB takes it
     */
    private static function conditionOfTheOperation(Write $write): array
    {
        $operation = $write->operation['Put'] ?? $write->operation['Delete'] ?? [];

        return array_intersect_key($operation, array_flip(['ConditionExpression', 'ExpressionAttributeValues', 'ExpressionAttributeNames']));
    }

    /**
     * An addition to an item's numbers. The item is created with them when it does not exist. What a transaction adds
     * is added to what another one added in the meantime. Two additions to the item in the same unit become one, their
     * sum.
     *
     * @param non-empty-array<string, int> $increments keyed by the number's name
     */
    public function adding(string $pk, string $sk, array $increments): Write
    {
        $names = array_keys($increments);
        $placeholders = array_map(static fn (int $position): string => 'n'.$position, array_keys($names));

        return new Write($this->name, $pk, $sk, ['Update' => [
            'TableName' => $this->name,
            'Key' => self::key($pk, $sk),
            'UpdateExpression' => 'ADD '.implode(', ', array_map(static fn (string $placeholder): string => sprintf('#%1$s :%1$s', $placeholder), $placeholders)),
            'ExpressionAttributeNames' => array_combine(array_map(static fn (string $placeholder): string => '#'.$placeholder, $placeholders), $names),
            'ExpressionAttributeValues' => $this->marshaler->marshalItem(array_combine(array_map(static fn (string $placeholder): string => ':'.$placeholder, $placeholders), array_values($increments))),
        ]], afterPending: fn (Write $pending): Write => $this->adding($pk, $sk, self::sum($this->incrementsOf($pending), $increments)));
    }

    /**
     * @return array<string, int> what an addition adds, keyed by the number's name
     */
    private function incrementsOf(Write $addition): array
    {
        $update = $addition->operation['Update'] ?? throw new \LogicException(sprintf('Only an addition is followed by an addition to %s %s', $addition->pk, $addition->sk));
        $values = $this->documentOf($update['ExpressionAttributeValues'] ?? null);
        $increments = [];

        foreach (Typed::namedStrings($update['ExpressionAttributeNames'] ?? null) as $placeholder => $name) {
            $increments[$name] = Typed::int($values[':'.substr($placeholder, 1)] ?? null);
        }

        return $increments;
    }

    /**
     * @param array<string, int>           $increments
     * @param non-empty-array<string, int> $others
     *
     * @return non-empty-array<string, int>
     */
    private static function sum(array $increments, array $others): array
    {
        $sum = $increments + $others;

        foreach ($others as $name => $increment) {
            $sum[$name] = ($increments[$name] ?? 0) + $increment;
        }

        return $sum;
    }

    /**
     * A delete in a transaction. When it has a condition, it is written only when the condition holds.
     *
     * @param array<string, mixed> $values the values the condition names
     */
    public function deleting(string $pk, string $sk, ?string $condition = null, array $values = []): Write
    {
        return new Write($this->name, $pk, $sk, ['Delete' => ['TableName' => $this->name, 'Key' => self::key($pk, $sk)] + $this->conditionOf($condition, $values)]);
    }

    /**
     * The delete of an aggregate, under an optimistic lock on its revision (ADR 0181).
     */
    public function deletingRevised(string $pk, string $sk): Write
    {
        $lock = $this->unitOfWork->lockOn($this->name, $pk, $sk);

        return null === $lock ? $this->deleting($pk, $sk) : $this->deleting($pk, $sk, $lock->condition, $lock->values);
    }

    /**
     * Writes all or nothing, together with what the open unit of work writes.
     *
     * @param list<Write> $writes made by putting(), replacing(), adding() and deleting()
     *
     * @throws ConditionFailed when the condition of a write without a refusal did not hold
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
            ...array_map(static fn (array $key): array => ['DeleteRequest' => ['Key' => self::key(...$key)]], $deletes),
        ];

        foreach (array_chunk($requests, self::BATCH_WRITE_SIZE) as $chunk) {
            while ([] !== $chunk) {
                $unprocessed = $this->client->batchWriteItem(['RequestItems' => [$this->name => $chunk]])['UnprocessedItems'] ?? [];
                $chunk = Typed::documents(Typed::document($unprocessed)[$this->name] ?? []);
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
            'ExpressionAttributeValues' => self::strings([':pk' => $partition, ':sk' => $sortKey]),
        ];
        $keys = [];
        $last = null;

        do {
            $result = $this->client->query($request + self::startingAfter($last));
            $keys = [...$keys, ...array_map(static fn (array $item): string => Typed::string($item['PK'] ?? null), $this->documentsOf($result['Items'] ?? []))];
            $last = $this->lastKeyOf($result);
        } while (null !== $last);

        return $keys;
    }

    /**
     * @return array<string, array{S: string}>
     */
    private static function key(string $pk, string $sk): array
    {
        return self::strings(['PK' => $pk, 'SK' => $sk]);
    }

    /**
     * @param array<string, string> $strings
     *
     * @return array<string, array{S: string}> as DynamoDB takes them
     */
    private static function strings(array $strings): array
    {
        return array_map(static fn (string $string): array => ['S' => $string], $strings);
    }

    /**
     * @param array<string, array{S: string}>|null $last
     *
     * @return array{ExclusiveStartKey?: array<string, array{S: string}>} where a query's or a scan's page starts
     */
    private static function startingAfter(?array $last): array
    {
        return null === $last ? [] : ['ExclusiveStartKey' => $last];
    }

    /**
     * The key of the last item that a query's or a scan's page read, to read the next page from. None after the last
     * page.
     *
     * @return array<string, array{S: string}>|null
     */
    private function lastKeyOf(ResultInterface $result): ?array
    {
        $last = $result['LastEvaluatedKey'] ?? null;

        return null === $last ? null : self::strings(Typed::namedStrings($this->documentOf($last)));
    }

    /**
     * @param array<string, mixed>  $values
     * @param array<string, string> $names
     *
     * @return array<string, mixed>
     */
    private function conditionOf(?string $condition, array $values, array $names = []): array
    {
        if (null === $condition) {
            return [];
        }

        return ['ConditionExpression' => $condition]
            + ([] === $values ? [] : ['ExpressionAttributeValues' => $this->marshaler->marshalItem($values)])
            + ([] === $names ? [] : ['ExpressionAttributeNames' => $names]);
    }

    /**
     * @return array<string, mixed> an item as DynamoDB gives it, as plain data
     */
    private function documentOf(mixed $item): array
    {
        return Typed::document($this->marshaler->unmarshalItem(Typed::array($item)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function documentsOf(mixed $items): array
    {
        return array_values(array_map($this->documentOf(...), Typed::array($items)));
    }
}
