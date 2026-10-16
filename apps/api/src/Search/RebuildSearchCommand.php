<?php

namespace App\Search;

use App\PersonalData\Persistence\PersonSearchIndex;
use App\Prospecting\Persistence\OrganizationSearchIndex;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds the search projections from the aggregates (ADR 0050): run it again whenever a projection
 * drifted, after an error between saving an aggregate and indexing it. It can run any number of times.
 */
#[AsCommand(name: 'app:search:rebuild', description: 'Rebuild the search of the organizations and of the persons from the aggregates')]
final readonly class RebuildSearchCommand
{
    public function __construct(private OrganizationSearchIndex $organizations, private PersonSearchIndex $persons)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->success(sprintf('Indexed %d organizations and %d persons.', $this->organizations->rebuild(), $this->persons->rebuild()));

        return Command::SUCCESS;
    }
}
