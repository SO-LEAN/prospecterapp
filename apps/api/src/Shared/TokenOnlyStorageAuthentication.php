<?php

namespace App\Shared;

/**
 * Locally, the internal server of the storage is on the Docker network only: the token of the services is enough.
 */
final readonly class TokenOnlyStorageAuthentication implements StorageAuthentication
{
    public function headersFor(string $method, string $url, array $headers, string $body): array
    {
        return $headers;
    }
}
