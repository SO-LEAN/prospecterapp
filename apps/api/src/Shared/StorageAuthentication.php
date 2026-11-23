<?php

declare(strict_types=1);

namespace App\Shared;

/**
 * How the API proves its identity to the storage's internal entry, beyond the services' token (ADR 0068).
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
