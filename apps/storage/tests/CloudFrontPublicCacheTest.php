<?php

namespace App\Tests;

use App\CloudFrontPublicCache;
use Aws\CloudFront\CloudFrontClient;
use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use PHPUnit\Framework\TestCase;

/**
 * On AWS, the copies CloudFront keeps of a removed file are invalidated, under the path of the public address.
 */
class CloudFrontPublicCacheTest extends TestCase
{
    public function testThePathsOfTheRemovedFilesAreInvalidated(): void
    {
        $commands = [];
        $handler = new MockHandler();
        $handler->append(static function (CommandInterface $command) use (&$commands): Result {
            $commands[] = $command;

            return new Result();
        });
        $cloudFront = new CloudFrontClient(['region' => 'eu-west-3', 'credentials' => ['key' => 'k', 'secret' => 's'], 'handler' => $handler]);

        new CloudFrontPublicCache($cloudFront, 'E2DISTRIBUTION', 'https://static.test')->forget(['2026/10/a/a.png', 'small/2026/10/a/a.png']);

        $this->assertSame('CreateInvalidation', $commands[0]->getName());
        $this->assertSame('E2DISTRIBUTION', $commands[0]['DistributionId']);
        $this->assertSame(['Quantity' => 2, 'Items' => ['/2026/10/a/a.png', '/small/2026/10/a/a.png']], $commands[0]['InvalidationBatch']['Paths']);
    }
}
