<?php

namespace App\Shared\Persistence;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

/**
 * The DynamoDB table of one bounded context (ADR 0038, 0048), in single-table design: every item is a plain
 * document (strings, numbers, booleans, null, lists and maps) keyed by `PK` and `SK`; the two secondary
 * indexes `GSI1` and `GSI2` list items by `GSI1PK`/`GSI1SK` and `GSI2PK`/`GSI2SK`, each repository giving
 * them their meaning; `NAME` and `EMAIL` keep only the keys of what the search finds (`SearchIndexes`).
 */
final readonly class Table
{
    public const string BY_GSI1 = 'GSI1';
    public const string BY_GSI2 = 'GSI2';
    public const string BY_NAME = 'NAME';
    public const string BY_EMAIL = 'EMAIL';
    private const int BATCH_GET_SIZE = 100;
    private const int BATCH_WRITE_SIZE = 25;

    public function __construct(private DynamoDbClient $client, private Marshaler $marshaler, public string $name)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function item(string $pk, string $sk): ?array
    {
        $item = $this->client->getItem(['TableName' => $this->name, 'Key' => $this->key($pk, $sk), 'ConsistentRead' => true])['Item'] ?? null;

        return null === $item ? null : $this->documentOf($item);
    }

    /**
     * @param list<array{string, string}> $keys PK and SK of each item
     *
     * @return list<array<string, mixed>> the items found, in no particular order
     */
    public function items(array $keys): array
    {
        $found = [];

        foreach (array_chunk(array_values(array_unique($keys, SORT_REGULAR)), self::BATCH_GET_SIZE) as $chunk) {
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
        [$pkName, $skName] = null === $index ? ['PK', 'SK'] : [$index.'PK', $index.'SK'];
        $request = [
            'TableName' => $this->name,
            'KeyConditionExpression' => null === $before ? '#pk = :pk' : '#pk = :pk AND #sk < :before',
            'ExpressionAttributeNames' => ['#pk' => $pkName] + (null === $before ? [] : ['#sk' => $skName]),
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':pk' => $partition] + (null === $before ? [] : [':before' => $before])),
            'ScanIndexForward' => false,
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
     * The partition keys of the items an index lists in a partition with a sort key beginning with
     * `beginning`, every one of them.
     *
     * @return list<string>
     */
    public function keysBeginningWith(string $index, string $partition, string $beginning): array
    {
        $request = [
            'TableName' => $this->name,
            'IndexName' => $index,
            'KeyConditionExpression' => '#pk = :pk AND begins_with(#sk, :beginning)',
            'ExpressionAttributeNames' => ['#pk' => $index.'PK', '#sk' => $index.'SK'],
            'ExpressionAttributeValues' => $this->marshaler->marshalItem([':pk' => $partition, ':beginning' => $beginning]),
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
     * A put of a transaction, written only when the condition holds.
     *
     * @param array<string, mixed> $item
     * @param array<string, mixed> $values the values the condition names
     *
     * @return array<string, mixed>
     */
    public function putting(array $item, ?string $condition = null, array $values = []): array
    {
        return ['Put' => ['TableName' => $this->name, 'Item' => $this->marshaler->marshalItem($item)] + $this->conditionOf($condition, $values)];
    }

    /**
     * A delete of a transaction, done only when the condition holds.
     *
     * @param array<string, mixed> $values the values the condition names
     *
     * @return array<string, mixed>
     */
    public function deleting(string $pk, string $sk, ?string $condition = null, array $values = []): array
    {
        return ['Delete' => ['TableName' => $this->name, 'Key' => $this->key($pk, $sk)] + $this->conditionOf($condition, $values)];
    }

    /**
     * Writes all or nothing.
     *
     * @param list<array<string, mixed>> $writes made by putting() and deleting()
     *
     * @throws ConditionFailed naming the writes whose condition did not hold
     */
    public function transact(array $writes): void
    {
        if ([] === $writes) {
            return;
        }

        try {
            $this->client->transactWriteItems(['TransactItems' => $writes]);
        } catch (DynamoDbException $failure) {
            throw 'TransactionCanceledException' === $failure->getAwsErrorCode() ? ConditionFailed::in($failure) : $failure;
        }
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
