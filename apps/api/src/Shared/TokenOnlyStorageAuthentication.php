<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * Locally, the storage's internal server is on the Docker network only, so the services' token is enough.
 */
final readonly class TokenOnlyStorageAuthentication implements StorageAuthentication
{
    public function headersFor(string $method, string $url, array $headers, string $body): array
    {
        return $headers;
    }
}
