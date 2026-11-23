<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * Where the member's browser goes back to the AI: the address that the AI gave, checked against its metadata
 * document, with the answer's parameters (RFC 6749, 4.1.2).
 */
final readonly class BackToTheAi
{
    /**
     * @param array<string, string> $parameters
     */
    public static function at(string $redirectUri, array $parameters): string
    {
        return $redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query($parameters);
    }
}
