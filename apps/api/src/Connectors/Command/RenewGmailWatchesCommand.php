<?php

declare(strict_types=1);

namespace App\Connectors\Command;

use App\Connectors\MailWatchesRenewer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Renews the watch of the mailbox of each import of the e-mails active (ADR 0154): by hand on the local stack, whose
 * MiniStack runs no schedule, or on an instance whose schedule missed a day.
 */
#[AsCommand(name: 'app:gmail:renew-watches', description: 'Renew the watch of the mailbox of each import of the e-mails of Gmail active')]
final readonly class RenewGmailWatchesCommand
{
    public function __construct(private MailWatchesRenewer $renewer)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $renewal = $this->renewer->renew();
        $io->success(sprintf('%d watch(es) of Gmail renewed, %d failed.', $renewal['renewed'], $renewal['failed']));

        return Command::SUCCESS;
    }
}
