<?php

declare(strict_types=1);

namespace App\Connectors\Command;

use App\Connectors\MailWatchesRenewer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Renews the mailbox watch of each active e-mail import (ADR 0154). Run it by hand on the local stack, because
 * MiniStack runs no schedule. Run it also on an instance whose schedule missed a day.
 */
#[AsCommand(name: 'app:gmail:renew-watches', description: 'Renew the mailbox watch of each active Gmail e-mail import')]
final readonly class RenewGmailWatchesCommand
{
    public function __construct(private MailWatchesRenewer $renewer)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $renewal = $this->renewer->renew();
        $io->success(sprintf('%d Gmail watch(es) renewed, %d failed.', $renewal['renewed'], $renewal['failed']));

        return Command::SUCCESS;
    }
}
