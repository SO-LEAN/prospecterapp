<?php

namespace App\Shared\Persistence;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * The tables of the bounded contexts (ADR 0038, 0048), all of the same shape: `PK`/`SK`, the indexes
 * `GSI1` and `GSI2`, and those of the search, `NAME` and `EMAIL`, keeping only the keys (ADR 0071); billed by
 * request. Locally they live in MiniStack; on AWS Terraform makes them alike (`infra/tables.tf`).
 */
final readonly class Tables
{
    private const array KEYS = ['PK', 'SK', 'GSI1PK', 'GSI1SK', 'GSI2PK', 'GSI2SK', 'NAMEPK', 'NAMESK', 'EMAILPK', 'EMAILSK'];

    public function __construct(
        private DynamoDbClient $client,
        private Table $accountsTable,
        private Table $personalDataTable,
        private Table $prospectingTable,
    ) {
    }

    /**
     * Drops the tables and creates them empty.
     */
    public function recreate(): void
    {
        foreach ($this->names() as $name) {
            $this->drop($name);
        }

        foreach ($this->names() as $name) {
            $this->create($name);
        }
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return [$this->accountsTable->name, $this->personalDataTable->name, $this->prospectingTable->name];
    }

    private function drop(string $name): void
    {
        try {
            $this->client->deleteTable(['TableName' => $name]);
            $this->client->waitUntil('TableNotExists', ['TableName' => $name]);
        } catch (DynamoDbException $failure) {
            if ('ResourceNotFoundException' !== $failure->getAwsErrorCode()) {
                throw $failure;
            }
        }
    }

    private function create(string $name): void
    {
        $this->client->createTable([
            'TableName' => $name,
            'BillingMode' => 'PAY_PER_REQUEST',
            'AttributeDefinitions' => array_map(static fn (string $key): array => ['AttributeName' => $key, 'AttributeType' => 'S'], self::KEYS),
            'KeySchema' => self::keySchema('PK', 'SK'),
            'GlobalSecondaryIndexes' => [
                ['IndexName' => Table::BY_GSI1, 'KeySchema' => self::keySchema('GSI1PK', 'GSI1SK'), 'Projection' => ['ProjectionType' => 'ALL']],
                ['IndexName' => Table::BY_GSI2, 'KeySchema' => self::keySchema('GSI2PK', 'GSI2SK'), 'Projection' => ['ProjectionType' => 'ALL']],
                ['IndexName' => Table::BY_NAME, 'KeySchema' => self::keySchema('NAMEPK', 'NAMESK'), 'Projection' => ['ProjectionType' => 'KEYS_ONLY']],
                ['IndexName' => Table::BY_EMAIL, 'KeySchema' => self::keySchema('EMAILPK', 'EMAILSK'), 'Projection' => ['ProjectionType' => 'KEYS_ONLY']],
            ],
        ]);
        $this->client->waitUntil('TableExists', ['TableName' => $name]);
    }

    /**
     * @return list<array{AttributeName: string, KeyType: string}>
     */
    private static function keySchema(string $partition, string $sort): array
    {
        return [['AttributeName' => $partition, 'KeyType' => 'HASH'], ['AttributeName' => $sort, 'KeyType' => 'RANGE']];
    }
}
