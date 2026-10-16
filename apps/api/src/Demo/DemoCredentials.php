<?php

namespace App\Demo;

use App\SignIn\Credentials;

/**
 * What the members of the demonstration sign in with (`make db`, the API tests): their login email and the
 * password `password`, under the member ids of DemoData.
 */
final readonly class DemoCredentials
{
    public const string PASSWORD = 'password';
    public const array LOGIN_EMAILS = [
        DemoData::PROSPECTOR => 'prospector@solean-it.io',
        DemoData::ASSISTANT => 'assistant@solean-it.io',
    ];

    public function __construct(private Credentials $credentials)
    {
    }

    public function register(): void
    {
        foreach (self::LOGIN_EMAILS as $memberId => $loginEmail) {
            $this->credentials->register($memberId, $loginEmail, self::PASSWORD);
        }
    }
}
