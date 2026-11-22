<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * What an AI declares of itself in its Client ID Metadata Document: where it may be sent back. A native client
 * listens on a loopback port of its session, never the same: on the loopback, the port is not matched (RFC 8252).
 */
final readonly class ClientMetadata
{
    private const string LOOPBACK_PORT = '~^(http://(?:localhost|127\.0\.0\.1|\[::1\])):\d+(?=[/?#]|$)~';

    /**
     * @param list<string> $redirectUris
     */
    public function __construct(private array $redirectUris)
    {
    }

    public function declares(string $redirectUri): bool
    {
        $given = self::withoutLoopbackPort($redirectUri);

        return array_any($this->redirectUris, static fn (string $declared): bool => self::withoutLoopbackPort($declared) === $given);
    }

    private static function withoutLoopbackPort(string $uri): string
    {
        return (string) preg_replace(self::LOOPBACK_PORT, '$1', $uri);
    }
}
