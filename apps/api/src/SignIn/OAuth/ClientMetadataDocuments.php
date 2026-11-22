<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The Client ID Metadata Documents of the AIs, read where their `client_id` says (ADR 0157): a URL anyone may give,
 * so in https only, never followed elsewhere, briefly, a little of it, and never on a private network (the client
 * injected refuses one, `NoPrivateNetworkHttpClient`).
 */
final readonly class ClientMetadataDocuments
{
    private const float SECONDS = 5.0;
    private const int LARGEST_DOCUMENT = 5 * 1024;

    public function __construct(private HttpClientInterface $http)
    {
    }

    /**
     * @throws UnknownClient
     */
    public function of(string $clientId): ClientMetadata
    {
        $document = $this->documentOf($clientId);

        if ($clientId !== ($document['client_id'] ?? null)) {
            throw UnknownClient::named($clientId, 'its document names another client');
        }

        $redirectUris = $document['redirect_uris'] ?? null;

        if (!self::isAListOfAddresses($redirectUris)) {
            throw UnknownClient::named($clientId, 'its document declares no list of redirect URIs');
        }

        return new ClientMetadata($redirectUris);
    }

    /**
     * @phpstan-assert-if-true list<string> $redirectUris
     */
    private static function isAListOfAddresses(mixed $redirectUris): bool
    {
        return is_array($redirectUris) && array_is_list($redirectUris) && array_all($redirectUris, static fn (mixed $uri): bool => is_string($uri));
    }

    /**
     * @return array<string, mixed>
     */
    private function documentOf(string $clientId): array
    {
        if ('https' !== parse_url($clientId, PHP_URL_SCHEME)) {
            throw UnknownClient::named($clientId, 'its document is not served in https');
        }

        try {
            return $this->http->request('GET', $clientId, [
                'max_redirects' => 0,
                'timeout' => self::SECONDS,
                'max_duration' => self::SECONDS,
                'on_progress' => self::stopBeyondTheLargestDocument(...),
            ])->toArray();
        } catch (ExceptionInterface $failure) {
            throw UnknownClient::named($clientId, $failure->getMessage());
        }
    }

    private static function stopBeyondTheLargestDocument(int $downloaded, int $size): void
    {
        if (max($downloaded, $size) > self::LARGEST_DOCUMENT) {
            throw new TransportException(sprintf('the document is larger than %d bytes', self::LARGEST_DOCUMENT));
        }
    }
}
