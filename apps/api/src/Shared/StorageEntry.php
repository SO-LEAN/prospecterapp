<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * The storage app's internal entry (ADR 0033). The API calls it with the platform services' token. On AWS, it also
 * signs the call with its role (ADR 0068).
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
