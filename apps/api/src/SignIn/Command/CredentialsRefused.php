<?php

declare(strict_types=1);

namespace App\SignIn\Command;

use Symfony\Component\Console\Command\Command;

/**
 * Refuses the credentials of a new member. The command's exit status tells how they were refused.
 */
final class CredentialsRefused extends \RuntimeException
{
    private function __construct(string $message, public readonly int $exitStatus)
    {
        parent::__construct($message);
    }

    public static function becauseTaken(string $email): self
    {
        return new self(sprintf('Someone already signs in with "%s".', $email), Command::FAILURE);
    }

    public static function becauseNobodySignsInWith(string $email): self
    {
        return new self(sprintf('Nobody signs in with "%s".', $email), Command::FAILURE);
    }

    public static function becauseTooShort(int $minimumLength): self
    {
        return new self(sprintf('The password needs at least %d characters.', $minimumLength), Command::INVALID);
    }
}
