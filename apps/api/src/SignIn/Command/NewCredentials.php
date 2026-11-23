<?php

declare(strict_types=1);

namespace App\SignIn\Command;

use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * What a new member signs in with, as the console commands take it: a login email that nobody else signs in with,
 * and a long enough password. The command asks for the password without echo when it is not given.
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
        $password ??= $io->askHidden(sprintf('Password for "%s"', $email));

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
