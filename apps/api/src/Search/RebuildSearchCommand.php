<?php

namespace App\Search;

use App\PersonalData\Persistence\DynamoPersonRepository;
use App\Prospecting\Persistence\DynamoOrganizationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes again the search keys of the organizations and of the persons (ADR 0071), forgetting the items of the
 * former search, before ADR 0071: once on tables written before, then whenever the keys of an aggregate change of
 * rule. It can run any number of times.
 */
#[AsCommand(name: 'app:search:rebuild', description: 'Write again the search keys of the organizations and of the persons')]
final readonly class RebuildSearchCommand
{
    public function __construct(private DynamoOrganizationRepository $organizations, private DynamoPersonRepository $persons)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->success(sprintf('Wrote the keys of %d organizations and %d persons.', $this->organizations->writeTheSearchKeysAgain(), $this->persons->writeTheSearchKeysAgain()));

        return Command::SUCCESS;
    }
}
