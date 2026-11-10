<?php

namespace Tests\App\Shared;

use App\Shared\Persistence\Tables;
use Aws\DynamoDb\DynamoDbClient;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Every table gives its new items to a stream, which the relay reads, and forgets an item past its `expiresAt`.
 */
#[Group('api')]
class TablesTest extends KernelTestCase
{
    public function testATableStreamsItsNewItemsAndForgetsThoseExpired(): void
    {
        $tables = self::getContainer()->get(Tables::class);
        $client = self::getContainer()->get(DynamoDbClient::class);
        $tables->recreate();

        foreach ($tables->names() as $name) {
            $this->assertSame(['StreamEnabled' => true, 'StreamViewType' => 'NEW_IMAGE'], $client->describeTable(['TableName' => $name])['Table']['StreamSpecification'], $name);
            $this->assertSame('expiresAt', $client->describeTimeToLive(['TableName' => $name])['TimeToLiveDescription']['AttributeName'] ?? null, $name);
        }
    }
}
