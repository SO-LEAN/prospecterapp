<?php

declare(strict_types=1);

namespace App\Prospecting\Command;

use App\Prospecting\ContactsLeftUnattachedEraser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Erases the contacts that nobody attached in the 30 days after their last meeting (ADR 0146). Run it by hand on the
 * local stack, because MiniStack runs no schedule. Run it also on an instance whose daily schedule missed a day (ADR
 * 0147, 0161).
 */
#[AsCommand(name: 'app:contacts:erase-unattached', description: 'Erase the contacts to attach that nobody attached in the 30 days after their last meeting')]
final readonly class EraseContactsLeftUnattachedCommand
{
    public function __construct(private ContactsLeftUnattachedEraser $eraser)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->success(sprintf('%d contact(s) left unattached erased.', $this->eraser->erase()));

        return Command::SUCCESS;
    }
}
