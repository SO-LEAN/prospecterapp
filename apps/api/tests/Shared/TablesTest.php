<?php

declare(strict_types=1);

namespace Tests\App\Shared;

use App\Shared\Persistence\Tables;
use App\Shared\Typed;
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
        $tables = Typed::object(self::getContainer()->get(Tables::class), Tables::class);
        $client = Typed::object(self::getContainer()->get(DynamoDbClient::class), DynamoDbClient::class);
        $tables->recreate();

        foreach ($tables->names() as $name) {
            $this->assertSame(['StreamEnabled' => true, 'StreamViewType' => 'NEW_IMAGE'], Typed::document($client->describeTable(['TableName' => $name])['Table'])['StreamSpecification'], $name);
            $this->assertSame('expiresAt', Typed::document($client->describeTimeToLive(['TableName' => $name])['TimeToLiveDescription'])['AttributeName'] ?? null, $name);
        }
    }
}
