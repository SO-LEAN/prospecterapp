<?php

namespace Tests\App\Shared;

use App\Shared\StorageClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The API talks to the storage app on its internal server, with the token of the services.
 */
#[Group('unit')]
class StorageClientTest extends TestCase
{
    public function testAFileIsSentAndItsPublicUrlKept(): void
    {
        $requests = [];
        $client = $this->client($requests, new MockResponse('{"url": "https://static.test/storage/2026/10/a/a.png"}', ['http_code' => 201]));
        $file = new \SplFileInfo(__DIR__.'/../Fixtures/logo.png');

        $this->assertSame('https://static.test/storage/2026/10/a/a.png', $client->add($file));
        $this->assertSame(['POST', 'http://storage.internal/files'], [$requests[0]['method'], $requests[0]['url']]);
        $this->assertContains('Authorization: Bearer the-token', $requests[0]['headers']);
        $this->assertStringContainsString('name="file"; filename="upload.png"', $requests[0]['body']);
    }

    public function testAFileIsRemovedByItsPathInTheStorage(): void
    {
        $requests = [];
        $this->client($requests, new MockResponse('', ['http_code' => 204]))->remove('https://static.test/storage/2026/10/a/a.png');

        $this->assertSame(['DELETE', 'http://storage.internal/files/2026/10/a/a.png'], [$requests[0]['method'], $requests[0]['url']]);
    }

    /**
     * @param list<array{method: string, url: string, headers: list<string>, body: string}> $requests
     */
    private function client(array &$requests, MockResponse $response): StorageClient
    {
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests, $response): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'headers' => $options['headers'], 'body' => self::bodyOf($options['body'] ?? '')];

            return $response;
        });

        return new StorageClient($http, 'http://storage.internal', 'https://static.test/storage', 'the-token');
    }

    /**
     * The body as the client prepares it: a string, or a closure giving it chunk by chunk.
     */
    private static function bodyOf(mixed $body): string
    {
        if (!$body instanceof \Closure) {
            return (string) $body;
        }

        $read = '';
        while ('' !== $chunk = $body(8192)) {
            $read .= $chunk;
        }

        return $read;
    }
}
