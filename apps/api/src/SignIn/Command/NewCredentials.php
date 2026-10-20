<?php

namespace App\SignIn\Command;

use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * What a new member signs in with, as the commands of the console take it: a login email nobody signs in with,
 * and a password long enough, asked without echo when not given.
 */
final readonly class NewCredentials
{
    public function __construct(private Credentials $credentials)
    {
    }

    /**
     * @throws CredentialsRefused
     */
    public function passwordFor(string $email, ?string $password, SymfonyStyle $io): string
    {
        $this->ensureNobodySignsInWith($email);
        $password ??= $io->askHidden(sprintf('Password of "%s"', $email));

        if (!is_string($password) || mb_strlen($password) < UserPool::PASSWORD_MINIMUM_LENGTH) {
            throw CredentialsRefused::becauseTooShort(UserPool::PASSWORD_MINIMUM_LENGTH);
        }

        return $password;
    }

    private function ensureNobodySignsInWith(string $email): void
    {
        if (null !== $this->credentials->memberSigningInWith($email)) {
            throw CredentialsRefused::becauseTaken($email);
        }
    }
}
