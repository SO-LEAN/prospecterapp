<?php

namespace App\SignIn\OAuth;

/**
 * Why the facade sealed a request, and for how long it opens: the time of signing in on Cognito, then that of an
 * authorization code (ADR 0157).
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
