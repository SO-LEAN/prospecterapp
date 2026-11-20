<?php

namespace Tests\App\Shared;

use App\Shared\Resource\UploadResource;
use App\Shared\UploadsSentByTheApi;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\File\File;

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

        $this->uploads($requests, 'http://ministack:4566')->send($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));

        $this->assertSame(['POST', 'http://ministack:4566/prospecter-dev-private-files'], [$requests[0]['method'], $requests[0]['url']]);
        $this->assertStringContainsString("name=\"key\"\r\n\r\nuploads/0123456789abcdef0123456789abcdef", $requests[0]['body']);
        $this->assertStringContainsString("name=\"Content-Type\"\r\n\r\nimage/png", $requests[0]['body']);
        $this->assertMatchesRegularExpression('/name="Content-Type".*name="file"; filename="logo.png"/s', $requests[0]['body']);
    }

    public function testWithoutAnAddressOfItsOwnTheApiSendsTheFileWhereTheFormSays(): void
    {
        $requests = [];

        $this->uploads($requests, '')->send($this->form(), new File(__DIR__.'/../Fixtures/logo.png'));

        $this->assertSame(self::FORM, $requests[0]['url']);
    }

    /**
     * @param list<array{method: string, url: string, body: string}> $requests
     */
    private function uploads(array &$requests, string $endpoint): UploadsSentByTheApi
    {
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'body' => self::bodyOf($options['body'] ?? '')];

            return new MockResponse('', ['http_code' => 204]);
        });

        return new UploadsSentByTheApi($http, $endpoint);
    }

    private function form(): UploadResource
    {
        return new UploadResource('0123456789abcdef0123456789abcdef', self::FORM, ['key' => 'uploads/0123456789abcdef0123456789abcdef', 'policy' => 'a-policy'], 10 * 1024 * 1024);
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
