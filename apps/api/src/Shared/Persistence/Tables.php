<?php

declare(strict_types=1);

namespace App\Shared\Persistence;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

/**
 * The bounded contexts' tables (ADR 0038, 0048). They all have the same shape: `PK`/`SK`, the `GSI1` and `GSI2`
 * indexes, and the indexes that find an aggregate, `NAME` and `EMAIL` (ADR 0071) and `PHONE` (ADR 0072), which keep
 * only the keys. They are billed by request. A stream receives their new items, and the outbox relay reads it. The
 * table forgets an item past its `expiresAt`. Locally, the tables live in MiniStack. On AWS, Terraform creates them
 * alike (`infra/tables.tf`).
 */
final readonly class Tables
{
    private const string EXPIRES_AT = 'expiresAt';
    private const array KEYS = ['PK', 'SK', 'GSI1PK', 'GSI1SK', 'GSI2PK', 'GSI2SK', 'NAMEPK', 'NAMESK', 'EMAILPK', 'EMAILSK', 'PHONEPK', 'PHONESK'];

    public function __construct(
        private DynamoDbClient $client,
        private Table $accountsTable,
        private Table $personalDataTable,
        private Table $prospectingTable,
        private Table $reportingTable,
        private Table $connectorsTable,
        private Table $campaignsTable,
        private Table $bookingTable,
        private AccountSecrets $accountSecrets,
    ) {
    }

    /**
     * Drops the tables and creates them empty. It also forgets the secrets that the accounts gave (ADR 0125), since their
     * items are gone with the tables.
     */
    public function recreate(): void
    {
        $this->accountSecrets->forgetEvery();

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
        return [$this->accountsTable->name, $this->personalDataTable->name, $this->prospectingTable->name, $this->reportingTable->name, $this->connectorsTable->name, $this->campaignsTable->name, $this->bookingTable->name];
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
                ['IndexName' => Table::BY_PHONE, 'KeySchema' => self::keySchema('PHONEPK', 'PHONESK'), 'Projection' => ['ProjectionType' => 'KEYS_ONLY']],
            ],
            'StreamSpecification' => ['StreamEnabled' => true, 'StreamViewType' => 'NEW_IMAGE'],
        ]);
        $this->client->waitUntil('TableExists', ['TableName' => $name]);
        $this->client->updateTimeToLive(['TableName' => $name, 'TimeToLiveSpecification' => ['Enabled' => true, 'AttributeName' => self::EXPIRES_AT]]);
    }

    /**
     * @return list<array{AttributeName: string, KeyType: 'HASH'|'RANGE'}>
     */
    private static function keySchema(string $partition, string $sort): array
    {
        return [['AttributeName' => $partition, 'KeyType' => 'HASH'], ['AttributeName' => $sort, 'KeyType' => 'RANGE']];
    }
}
