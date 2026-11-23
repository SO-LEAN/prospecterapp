<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * The facade's token endpoint refuses the AI's request with an RFC 6749 error (5.2).
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
        return new self('unsupported_grant_type', sprintf('The grant type %s is not supported.', $grantType));
    }
}
