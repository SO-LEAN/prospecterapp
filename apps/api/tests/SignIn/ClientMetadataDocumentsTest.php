<?php

declare(strict_types=1);

namespace Tests\App\SignIn;

use App\SignIn\OAuth\ClientMetadataDocuments;
use App\SignIn\OAuth\UnknownClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\DnsResolvingHttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The facade reads the Client ID Metadata Document an AI names as its client: a URL anyone may give, read with care
 * (ADR 0157), the names of its hosts resolved by a double, the network out of reach.
 */
class ClientMetadataDocumentsTest extends TestCase
{
    private const string CLAUDE_CODE = 'https://claude.ai/oauth/claude-code-client-metadata';

    public function testTheDocumentOfAClientNamesWhereItIsSentBack(): void
    {
        $documents = $this->documentsAnswering(new JsonMockResponse(['client_id' => self::CLAUDE_CODE, 'redirect_uris' => ['http://localhost/callback']]));

        $this->assertTrue($documents->of(self::CLAUDE_CODE)->declares('http://localhost:53124/callback'));
    }

    public function testADocumentNamingAnotherClientIsRefused(): void
    {
        $documents = $this->documentsAnswering(new JsonMockResponse(['client_id' => 'https://claude.ai/another', 'redirect_uris' => ['http://localhost/callback']]));

        $this->expectException(UnknownClient::class);

        $documents->of(self::CLAUDE_CODE);
    }

    public function testAClientOnAPrivateNetworkIsRefused(): void
    {
        $documents = $this->documentsAnswering(new JsonMockResponse(['client_id' => 'https://internal.example/client', 'redirect_uris' => ['http://localhost/callback']]));

        $this->expectException(UnknownClient::class);

        $documents->of('https://internal.example/client');
    }

    public function testAClientNotInHttpsIsRefused(): void
    {
        $documents = $this->documentsAnswering(new JsonMockResponse(['client_id' => 'http://claude.ai/client', 'redirect_uris' => ['http://localhost/callback']]));

        $this->expectException(UnknownClient::class);

        $documents->of('http://claude.ai/client');
    }

    public function testADocumentSendingElsewhereIsNotFollowed(): void
    {
        $documents = $this->documentsAnswering(
            new MockResponse('', ['http_code' => 302, 'redirect_url' => 'https://claude.ai/elsewhere', 'response_headers' => ['Location' => 'https://claude.ai/elsewhere']]),
            new JsonMockResponse(['client_id' => self::CLAUDE_CODE, 'redirect_uris' => ['http://localhost/callback']]),
        );

        $this->expectException(UnknownClient::class);

        $documents->of(self::CLAUDE_CODE);
    }

    public function testADocumentOfMoreThanFiveKilobytesIsRefused(): void
    {
        $documents = $this->documentsAnswering(new JsonMockResponse(['client_id' => self::CLAUDE_CODE, 'redirect_uris' => ['http://localhost/callback'], 'client_name' => str_repeat('C', 5 * 1024)]));

        $this->expectException(UnknownClient::class);

        $documents->of(self::CLAUDE_CODE);
    }

    public function testADocumentDeclaringNoRedirectIsRefused(): void
    {
        $documents = $this->documentsAnswering(new JsonMockResponse(['client_id' => self::CLAUDE_CODE, 'redirect_uris' => 'http://localhost/callback']));

        $this->expectException(UnknownClient::class);

        $documents->of(self::CLAUDE_CODE);
    }

    private function documentsAnswering(MockResponse ...$answers): ClientMetadataDocuments
    {
        $network = new NoPrivateNetworkHttpClient(new MockHttpClient($answers));

        return new ClientMetadataDocuments(new DnsResolvingHttpClient($network, static fn (string $host): ?string => ['claude.ai' => '160.79.104.10', 'internal.example' => '10.0.0.7'][$host] ?? null));
    }
}
