<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Demo\DemoData;
use App\Demo\DemoMembers;
use App\Shared\Persistence\Tables;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gives a running stack the demonstration of `make db` back, before its journeys (`make check-e2e`): the tables are
 * emptied and the demonstration is loaded again, and the user pool gets its members back. The tables keep their streams,
 * so the relay publishes what the demonstration loads, and the pool keeps its clients.
 */
#[AsCommand(name: 'app:demo:restore', description: 'Give the tables and the user pool the demonstration back, without recreating them')]
final readonly class RestoreDemonstrationCommand
{
    public function __construct(private Tables $tables, private DemoData $demoData, private DemoMembers $demoMembers)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->tables->empty();
        $this->demoData->load();
        $this->demoData->addAContactToAttach();
        $this->demoMembers->restore();

        $io->success(sprintf('Gave the tables %s and the user pool the demonstration back.', implode(', ', $this->tables->names())));

        return Command::SUCCESS;
    }
}
