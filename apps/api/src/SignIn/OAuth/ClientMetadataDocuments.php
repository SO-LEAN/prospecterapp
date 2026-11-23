<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

use App\Shared\Typed;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reads the AIs' Client ID Metadata Documents at the URL that their `client_id` gives (ADR 0157). Anyone may give
 * such a URL. So the reader uses https only, never follows a redirect, waits briefly and reads a small document at
 * most. It never reaches a private network: the injected client refuses one (`NoPrivateNetworkHttpClient`).
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
            return Typed::document($this->http->request('GET', $clientId, [
                'max_redirects' => 0,
                'timeout' => self::SECONDS,
                'max_duration' => self::SECONDS,
                'on_progress' => self::stopBeyondTheLargestDocument(...),
            ])->toArray());
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
