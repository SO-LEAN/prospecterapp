<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * The proof key of a code (RFC 7636), S256 only: a verifier, and the challenge it answers.
 */
final readonly class Pkce
{
    public const string METHOD = 'S256';
    private const int VERIFIER_BYTES = 32;

    public static function newVerifier(): string
    {
        return self::base64Url(random_bytes(self::VERIFIER_BYTES));
    }

    public static function challengeOf(string $verifier): string
    {
        return self::base64Url(hash('sha256', $verifier, true));
    }

    public static function answers(string $verifier, string $challenge): bool
    {
        return hash_equals($challenge, self::challengeOf($verifier));
    }

    private static function base64Url(string $bytes): string
    {
        return sodium_bin2base64($bytes, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }
}
