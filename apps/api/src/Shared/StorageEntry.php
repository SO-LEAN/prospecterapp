<?php

namespace App\Shared;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The internal entry of the storage app (ADR 0033): the API calls it with the token of the services of the platform
 * and, on AWS, the signature of its role (ADR 0068).
 */
final readonly class StorageEntry
{
    private const string TOKEN_HEADER = 'X-Storage-Token';

    public function __construct(
        private HttpClientInterface $httpClient,
        private StorageAuthentication $authentication,
        #[Autowire(env: 'STORAGE_INTERNAL_URL')]
        private string $internalUrl,
        #[Autowire(env: 'STORAGE_TOKEN')]
        private string $token,
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public function send(string $method, string $path, array $headers = [], string $body = ''): ResponseInterface
    {
        $url = $this->internalUrl.$path;
        $headers = $this->authentication->headersFor($method, $url, [...$headers, self::TOKEN_HEADER => $this->token], $body);

        return $this->httpClient->request($method, $url, ['headers' => $headers, 'body' => $body]);
    }
}
