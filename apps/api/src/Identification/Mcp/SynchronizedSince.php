<?php

namespace App\Identification\Mcp;

/**
 * Since when a synchronization reads, in the words of the member, or asked to them when they did not say it.
 */
final readonly class SynchronizedSince
{
    public static function said(?string $since): string
    {
        return null === $since || '' === trim($since) ? ', en me demandant d\'abord depuis quand' : ' depuis '.trim($since);
    }
}
