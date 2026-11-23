<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Persistence\AccountSecrets;
use App\Shared\Persistence\Tables;
use App\Shared\Typed;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Every table gives its new items to a stream, which the relay reads, and forgets an item past its `expiresAt`. Emptied,
 * the tables keep their streams, so the relay goes on reading them.
 */
#[Group('api')]
class TablesTest extends KernelTestCase
{
    public function testATableStreamsItsNewItemsAndForgetsThoseExpired(): void
    {
        $tables = $this->tables();
        $tables->recreate();

        foreach ($tables->names() as $name) {
            $this->assertSame(['StreamEnabled' => true, 'StreamViewType' => 'NEW_IMAGE'], Typed::document($this->client()->describeTable(['TableName' => $name])['Table'])['StreamSpecification'], $name);
            $this->assertSame('expiresAt', Typed::document($this->client()->describeTimeToLive(['TableName' => $name])['TimeToLiveDescription'])['AttributeName'] ?? null, $name);
        }
    }

    public function testEmptyingTheTablesRemovesTheirItemsAndKeepsTheirStreams(): void
    {
        $tables = $this->tables();
        $tables->recreate();
        $streams = $this->streamsOf($tables);

        foreach ($tables->names() as $name) {
            $this->client()->putItem(['TableName' => $name, 'Item' => ['PK' => ['S' => 'ACCOUNT#a'], 'SK' => ['S' => 'ITEM#'.$name]]]);
        }

        $tables->empty();

        foreach ($tables->names() as $name) {
            $this->assertSame([], $this->client()->scan(['TableName' => $name, 'ConsistentRead' => true])['Items'], $name);
        }
        $this->assertSame($streams, $this->streamsOf($tables));
    }

    public function testEmptyingTheTablesForgetsTheSecretsOfTheAccounts(): void
    {
        $secrets = Typed::object(self::getContainer()->get(AccountSecrets::class), AccountSecrets::class);
        $version = $secrets->put('01900000-0000-7000-8000-0000000000e1', 'google', 'a secret');

        $this->tables()->empty();

        $this->assertNull($secrets->find('01900000-0000-7000-8000-0000000000e1', 'google', $version));
    }

    /**
     * @return array<string, string> the stream of each table, by its name
     */
    private function streamsOf(Tables $tables): array
    {
        return array_combine($tables->names(), array_map(fn (string $name): string => Typed::string(Typed::document($this->client()->describeTable(['TableName' => $name])['Table'])['LatestStreamArn']), $tables->names()));
    }

    private function tables(): Tables
    {
        return Typed::object(self::getContainer()->get(Tables::class), Tables::class);
    }

    private function client(): DynamoDbClient
    {
        return Typed::object(self::getContainer()->get(DynamoDbClient::class), DynamoDbClient::class);
    }
}
