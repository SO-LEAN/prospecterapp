<?php

namespace App\SignIn\OAuth;

/**
 * An AI whose Client ID Metadata Document cannot be read, or does not declare what it asks: the facade sends nobody
 * anywhere for it (ADR 0157).
 */
final class UnknownClient extends \RuntimeException
{
    public static function named(string $clientId, string $why): self
    {
        return new self(sprintf('The client "%s" is unknown: %s.', $clientId, $why));
    }
}
