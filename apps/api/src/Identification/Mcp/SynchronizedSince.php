<?php

declare(strict_types=1);

namespace App\Identification\Mcp;

/**
 * How far back a sync reads, in the member's own words, or a request to ask them when they did not say it clearly: a
 * client may split the arguments at the spaces and pass "7" for "7 days".
 */
final readonly class SynchronizedSince
{
    private const string ASK = 'First ask me how far back to go.';

    private const string ASK_WHEN_UNCLEAR = 'When this is not a clear moment, ask me how far back to go first. Do not guess.';

    public static function said(?string $since): string
    {
        return null === $since || '' === trim($since) ? self::ASK : 'How far back: '.trim($since).'. '.self::ASK_WHEN_UNCLEAR;
    }
}
