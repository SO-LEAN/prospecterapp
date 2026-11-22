<?php

declare(strict_types=1);

namespace App\Identification\Mcp;

/**
 * How far back a sync reads, in the member's own words, or a request to ask them when they did not say.
 */
final readonly class SynchronizedSince
{
    public static function said(?string $since): string
    {
        return null === $since || '' === trim($since) ? 'First ask me how far back to go.' : 'How far back: '.trim($since).'.';
    }
}
