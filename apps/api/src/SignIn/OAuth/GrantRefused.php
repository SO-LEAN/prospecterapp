<?php

namespace App\SignIn\OAuth;

/**
 * The token endpoint of the facade refuses what the AI asks, by an error of RFC 6749 (5.2).
 */
final class GrantRefused extends \RuntimeException
{
    private function __construct(public readonly string $error, string $description, ?\Throwable $previous = null)
    {
        parent::__construct($description, previous: $previous);
    }

    public static function because(string $description, ?\Throwable $previous = null): self
    {
        return new self('invalid_grant', $description, $previous);
    }

    public static function ofTheType(string $grantType): self
    {
        return new self('unsupported_grant_type', sprintf('The grant %s is not given.', $grantType));
    }
}
