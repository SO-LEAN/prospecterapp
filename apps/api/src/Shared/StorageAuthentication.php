<?php

namespace App\Shared;

/**
 * How the api proves itself to the internal entry of the storage, beyond the token of the services (ADR 0068).
 */
interface StorageAuthentication
{
    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string> the headers to send
     */
    public function headersFor(string $method, string $url, array $headers, string $body): array;
}
