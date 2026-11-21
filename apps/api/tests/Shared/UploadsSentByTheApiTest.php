<?php

namespace Tests\App\Shared;

use App\Shared\Resource\UploadResource;
use App\Shared\ServiceUnavailable;
use App\Shared\UploadsSentByTheApi;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;

/**
 * The API sends a file to the private bucket with the form of an upload, as a browser does (ADR 0172): to the bucket
 * at the address the API reaches it at, the origin of the form replaced, or at that of the form.
 */
#[Group('unit')]
class UploadsSentByTheApiTest extends TestCase
{
    private const string FORM = 'https://static.prospecter.localhost/prospecter-dev-private-files';

    public function testTheFileIsSentWithTheFieldsOfTheFormAndItsTypeToTheBucketWhereTheApiReachesIt(): void
    {
        $requests = [];

        $this->uploads($requests, 'http://ministack:4566')->upload($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));

        $this->assertSame(['POST', 'http://ministack:4566/prospecter-dev-private-files'], [$requests[0]['method'], $requests[0]['url']]);
        $this->assertStringContainsString("name=\"key\"\r\n\r\nuploads/0123456789abcdef0123456789abcdef", $requests[0]['body']);
        $this->assertStringContainsString("name=\"Content-Type\"\r\n\r\nimage/png", $requests[0]['body']);
        $this->assertMatchesRegularExpression('/name="Content-Type".*name="file"; filename="logo.png"/s', $requests[0]['body']);
    }

    public function testWithoutAnAddressOfItsOwnTheApiSendsTheFileWhereTheFormSays(): void
    {
        $requests = [];

        $this->uploads($requests, '')->upload($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));

        $this->assertSame(self::FORM, $requests[0]['url']);
    }

    /**
     * S3 refuses a form sent in chunks, without its length, which MiniStack takes.
     */
    public function testTheFileIsSentWithItsLengthAsS3RequiresIt(): void
    {
        $requests = [];

        $this->uploads($requests, '')->upload($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));

        $this->assertSame(['Content-Length: '.strlen($requests[0]['body'])], $requests[0]['length']);
    }

    public function testTheBucketRefusingTheFileMakesThePrivateFilesUnavailableForNow(): void
    {
        $http = new MockHttpClient(new MockResponse('<Error><Code>NotImplemented</Code></Error>', ['http_code' => 501]));

        try {
            new UploadsSentByTheApi($http, '')->upload($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));
            $this->fail('The file was kept');
        } catch (ServiceUnavailable $unavailable) {
            $this->assertSame('privateFilesUnavailable', $unavailable->reason);
        }
    }

    public function testTheBucketLimitingTheRequestsMakesThePrivateFilesUnavailableForNow(): void
    {
        $http = new MockHttpClient(new MockResponse('<Error><Code>SlowDown</Code></Error>', ['http_code' => 429]));

        $this->expectException(ServiceUnavailable::class);

        new UploadsSentByTheApi($http, '')->upload($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));
    }

    /**
     * The form of the API itself refused (a policy expired, a signature): trying again later would change nothing.
     */
    public function testTheBucketForbiddingTheFormIsAFailureOfTheApi(): void
    {
        $http = new MockHttpClient(new MockResponse('<Error><Code>AccessDenied</Code></Error>', ['http_code' => 403]));

        $this->expectException(ClientExceptionInterface::class);

        new UploadsSentByTheApi($http, '')->upload($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));
    }

    /**
     * @param list<array{method: string, url: string, body: string, length: list<string>}> $requests
     */
    private function uploads(array &$requests, string $endpoint): UploadsSentByTheApi
    {
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'body' => (string) $options['body'], 'length' => $options['normalized_headers']['content-length'] ?? []];

            return new MockResponse('', ['http_code' => 204]);
        });

        return new UploadsSentByTheApi($http, $endpoint);
    }

    private function form(): UploadResource
    {
        return new UploadResource('0123456789abcdef0123456789abcdef', self::FORM, ['key' => 'uploads/0123456789abcdef0123456789abcdef', 'policy' => 'a-policy'], 10 * 1024 * 1024);
    }
}
