<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Demo\DemoData;
use App\Demo\DemoMembers;
use App\Reporting\RebuildOfTheFigures;
use App\Shared\Persistence\Tables;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Gives a running stack the demonstration of `make db` back, before its journeys (`make check-e2e`): the tables are
 * emptied and the demonstration is loaded again, and the user pool gets its members back. The tables keep their streams,
 * so the relay publishes what the demonstration loads, and the pool keeps its clients.
 *
 * It then counts the demonstration's figures, before any gesture. Reporting's table was emptied with the others, so no
 * version is live. A recording of what the relay publishes records nothing until the count starts its version. After
 * that, it records the same facts that the count reads from the state. So the count's first attempt waits for no
 * recording. A conflict shows that the worker records in the version being built, so a retry waits for it (ADR 0209).
 */
#[AsCommand(name: 'app:demo:restore', description: 'Give the tables and the user pool the demonstration back, without recreating them')]
final readonly class RestoreDemonstrationCommand
{
    public function __construct(private Tables $tables, private DemoData $demoData, private DemoMembers $demoMembers, private RebuildOfTheFigures $countFigures)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->tables->empty();
        $this->demoData->load();
        $this->demoData->addAContactToAttach();
        $this->demoMembers->restore();
        $this->countFigures->run($io);

        $io->success(sprintf('Gave the tables %s and the user pool the demonstration back.', implode(', ', $this->tables->names())));

        return Command::SUCCESS;
    }
}
