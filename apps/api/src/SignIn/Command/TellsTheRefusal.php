<?php

namespace App\SignIn\Command;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A command of the console that registers somebody tells why it was refused, and exits accordingly.
 */
trait TellsTheRefusal
{
    private static function refused(CredentialsRefused|UseCaseRefused $refusal, SymfonyStyle $io): int
    {
        $io->error($refusal->getMessage());

        return $refusal instanceof CredentialsRefused ? $refusal->exitStatus : Command::FAILURE;
    }
}
