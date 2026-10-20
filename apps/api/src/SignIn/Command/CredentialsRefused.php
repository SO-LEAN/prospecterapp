<?php

namespace App\SignIn\Command;

use Symfony\Component\Console\Command\Command;

/**
 * What a new member would sign in with is refused, and the exit status of the command says how.
 */
final class CredentialsRefused extends \RuntimeException
{
    private function __construct(string $message, public readonly int $exitStatus)
    {
        parent::__construct($message);
    }

    public static function becauseTaken(string $email): self
    {
        return new self(sprintf('Somebody signs in with "%s" already.', $email), Command::FAILURE);
    }

    public static function becauseNobodySignsInWith(string $email): self
    {
        return new self(sprintf('Nobody signs in with "%s".', $email), Command::FAILURE);
    }

    public static function becauseTooShort(int $minimumLength): self
    {
        return new self(sprintf('The password needs %d characters at least.', $minimumLength), Command::INVALID);
    }
}
