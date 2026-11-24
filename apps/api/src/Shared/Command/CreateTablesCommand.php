<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Demo\DemoData;
use App\Demo\DemoFigures;
use App\Shared\Persistence\Tables;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recreates the bounded contexts' tables empty, with the demonstration data on request (`make db`). With the
 * demonstration, it counts its figures: no relay reads the new tables yet, so nothing else writes (ADR 0209).
 */
#[AsCommand(name: 'app:tables:recreate', description: 'Drop and create the bounded contexts\' DynamoDB tables (destroys their data)')]
final readonly class CreateTablesCommand
{
    public function __construct(private Tables $tables, private DemoData $demoData, private DemoFigures $demoFigures)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'Load the demonstration data and count its figures')] bool $demo = false): int
    {
        $this->tables->recreate();

        if ($demo) {
            $this->loadTheDemonstration($io);
        }

        $io->success(sprintf('Tables %s recreated%s.', implode(', ', $this->tables->names()), $demo ? ' with the data of the demonstration' : ''));

        return Command::SUCCESS;
    }

    private function loadTheDemonstration(SymfonyStyle $io): void
    {
        $this->demoData->load();
        $this->demoData->addAContactToAttach();
        $this->demoFigures->count($io);
    }
}
