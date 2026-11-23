<?php

declare(strict_types=1);

namespace App\SignIn\Command;

use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * A console command that registers someone tells why it was refused, and exits with the matching status.
 */
trait TellsTheRefusal
{
    private static function refused(CredentialsRefused|UseCaseRefused $refusal, SymfonyStyle $io): int
    {
        $io->error($refusal->getMessage());

        return $refusal instanceof CredentialsRefused ? $refusal->exitStatus : Command::FAILURE;
    }
}
