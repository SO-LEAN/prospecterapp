<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Demo\DemoData;
use App\Shared\Persistence\Tables;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recreates the bounded contexts' tables empty, with the demonstration data on request (`make db`).
 */
#[AsCommand(name: 'app:tables:recreate', description: 'Drop and create the bounded contexts\' DynamoDB tables (destroys their data)')]
final readonly class CreateTablesCommand
{
    public function __construct(private Tables $tables, private DemoData $demoData)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'Load the demonstration data')] bool $demo = false): int
    {
        $this->tables->recreate();

        if ($demo) {
            $this->demoData->load();
            $this->demoData->addAContactToAttach();
        }

        $io->success(sprintf('Tables %s recreated%s.', implode(', ', $this->tables->names()), $demo ? ' with the data of the demonstration' : ''));

        return Command::SUCCESS;
    }
}
