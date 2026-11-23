<?php

declare(strict_types=1);

namespace Tests\App\Prospecting;

use App\Prospecting\Storage\PrivateStorageClient;
use App\Shared\StorageEntry;
use App\Shared\TokenOnlyStorageAuthentication;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\Port\Upload;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownUpload;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The API reaches the private files on the internal entry of the storage, with the token of the services (ADR 0077).
 */
#[Group('unit')]
class PrivateStorageClientTest extends TestCase
{
    private const string UPLOAD = '0123456789abcdef0123456789abcdef';

    /** @var list<array{method: string, url: string}> */
    private array $requests = [];

    public function testWhatTheStorageMeasuredOfAnUploadIsRead(): void
    {
        $uploaded = $this->client(new MockResponse('{"size": 2048, "mediaType": "application/pdf"}'))->uploaded(self::UPLOAD);

        $this->assertEquals(new Upload(2048, 'application/pdf'), $uploaded);
        $this->assertSame([['method' => 'GET', 'url' => 'http://storage.internal/uploads/'.self::UPLOAD]], $this->requests);
    }

    public function testAnUploadTheStorageDoesNotHaveIsUnknown(): void
    {
        $this->expectException(UnknownUpload::class);

        $this->client(new MockResponse('', ['http_code' => 404]))->keep(self::UPLOAD);
    }

    public function testAnUploadIsNamedByTheStorageOnly(): void
    {
        $this->expectException(UnknownUpload::class);

        $this->client(new MockResponse('{}'))->uploaded('../files/2026');
    }

    public function testALinkIsAskedUnderTheNameOfTheAttachment(): void
    {
        $url = $this->client(new MockResponse('{"url": "https://s3.test/kept/x"}'))->downloadLinkOf(self::UPLOAD, 'Cahier des charges.pdf');

        $this->assertSame('https://s3.test/kept/x', $url);
        $this->assertSame('http://storage.internal/private-files/'.self::UPLOAD.'/download-link?name=Cahier+des+charges.pdf', $this->requests[0]['url']);
    }

    private function client(MockResponse $response): PrivateStorageClient
    {
        $http = new MockHttpClient(function (string $method, string $url) use ($response): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => $url];

            return $response;
        });

        return new PrivateStorageClient(new StorageEntry($http, new TokenOnlyStorageAuthentication(), 'http://storage.internal', 'the-token'));
    }
}
