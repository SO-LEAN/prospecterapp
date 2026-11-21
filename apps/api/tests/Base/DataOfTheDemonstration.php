<?php

namespace Tests\App\Base;

use App\Demo\DemoData;
use App\Shared\Persistence\AccountSecrets;
use App\Shared\Persistence\Tables;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Ssm\SsmClient;

/**
 * The data of the demonstration every API test starts from (ADR 0048): made once in each process of the tests, as the
 * use cases make them, on tables recreated, then kept as the items of the tables and the secrets of the accounts. Each
 * test after gets them back: the items a test changed or removed written again, those it added deleted, the secrets put
 * back, the tables kept.
 */
final class DataOfTheDemonstration
{
    private const int BATCH_WRITE_SIZE = 25;

    /** @var array<string, array<string, array<string, mixed>>>|null the items of each table, by their key */
    private static ?array $items = null;

    /** @var array<string, string> the secrets of the accounts, by name */
    private static array $secrets = [];

    public function __construct(
        private readonly Tables $tables,
        private readonly DemoData $demoData,
        private readonly DynamoDbClient $dynamoDb,
        private readonly AccountSecrets $accountSecrets,
        private readonly SsmClient $ssm,
        private readonly string $secretsPath,
    ) {
    }

    public function restore(): void
    {
        if (null === self::$items) {
            $this->make();

            return;
        }

        $this->restoreTheSecrets();

        foreach (self::$items as $table => $items) {
            $this->restoreTheItems($table, $items);
        }
    }

    private function make(): void
    {
        $this->tables->recreate();
        $this->demoData->load();
        self::$items = array_combine($this->tables->names(), array_map($this->itemsOf(...), $this->tables->names()));
        self::$secrets = $this->secrets();
    }

    private function restoreTheSecrets(): void
    {
        $this->accountSecrets->forgetEvery();

        foreach (self::$secrets as $name => $value) {
            $this->ssm->putParameter(['Name' => $name, 'Value' => $value, 'Type' => 'SecureString']);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $kept
     */
    private function restoreTheItems(string $table, array $kept): void
    {
        $current = $this->itemsOf($table);
        $added = array_diff_key($current, $kept);
        $changed = array_filter($kept, static fn (array $item, string $key): bool => ($current[$key] ?? null) !== $item, ARRAY_FILTER_USE_BOTH);

        $this->write($table, [
            ...array_map(static fn (array $item): array => ['DeleteRequest' => ['Key' => ['PK' => $item['PK'], 'SK' => $item['SK']]]], array_values($added)),
            ...array_map(static fn (array $item): array => ['PutRequest' => ['Item' => $item]], array_values($changed)),
        ]);
    }

    /**
     * @param list<array<string, mixed>> $requests
     */
    private function write(string $table, array $requests): void
    {
        foreach (array_chunk($requests, self::BATCH_WRITE_SIZE) as $chunk) {
            $pending = [$table => $chunk];

            while ([] !== $pending) {
                $pending = $this->dynamoDb->batchWriteItem(['RequestItems' => $pending])['UnprocessedItems'] ?? [];
            }
        }
    }

    /**
     * @return array<string, array<string, mixed>> every item of the table as DynamoDB gives it, its maps in the order of
     *                                             their keys, by its key
     */
    private function itemsOf(string $table): array
    {
        $request = ['TableName' => $table, 'ConsistentRead' => true];
        $items = [];

        do {
            $result = $this->dynamoDb->scan($request);

            foreach ($result['Items'] ?? [] as $item) {
                $items[$item['PK']['S'].'|'.$item['SK']['S']] = self::canonical($item);
            }

            $request['ExclusiveStartKey'] = $result['LastEvaluatedKey'] ?? null;
        } while (null !== $request['ExclusiveStartKey']);

        return $items;
    }

    /**
     * @return array<string, string>
     */
    private function secrets(): array
    {
        $secrets = [];

        foreach ($this->ssm->getPaginator('GetParametersByPath', ['Path' => $this->secretsPath, 'Recursive' => true, 'WithDecryption' => true]) as $page) {
            $secrets = [...$secrets, ...array_column($page['Parameters'], 'Value', 'Name')];
        }

        return $secrets;
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<array-key, mixed>
     */
    private static function canonical(array $value): array
    {
        ksort($value);

        return array_map(static fn (mixed $member): mixed => is_array($member) ? self::canonical($member) : $member, $value);
    }
}
