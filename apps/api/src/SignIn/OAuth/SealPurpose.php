<?php

declare(strict_types=1);

namespace App\SignIn\OAuth;

/**
 * Why the facade sealed a request, and how long the seal stays valid. A sign-in seal lasts the time of a sign-in on
 * Cognito. An authorization-code seal lasts an authorization code's lifetime (ADR 0157).
 */
enum SealPurpose: string
{
    case SignIn = 'sign-in';
    case AuthorizationCode = 'authorization-code';

    public function lifetime(): string
    {
        return match ($this) {
            self::SignIn => '+15 minutes',
            self::AuthorizationCode => '+5 minutes',
        };
    }
}
