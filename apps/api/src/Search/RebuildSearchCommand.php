<?php

namespace App\Search;

use App\Accounts\Persistence\DynamoAccountRepository;
use App\PersonalData\Persistence\DynamoPersonRepository;
use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Prospecting\Persistence\DynamoOrganizationRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes again the search keys of the organizations and of the persons (ADR 0071), forgetting the items of the
 * former search, before ADR 0071, the keys listing the exchanges by account and the keys listing the accounts
 * (ADR 0099): once on tables written before, then whenever the keys of an aggregate change of rule. It can run any
 * number of times.
 */
#[AsCommand(name: 'app:search:rebuild', description: 'Write again the search keys of the organizations and of the persons, and the keys of the exchanges and of the accounts')]
final readonly class RebuildSearchCommand
{
    public function __construct(
        private DynamoOrganizationRepository $organizations,
        private DynamoPersonRepository $persons,
        private DynamoExchangeRepository $exchanges,
        private DynamoAccountRepository $accounts,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->success(sprintf(
            'Wrote the keys of %d organizations, %d persons, %d exchanges and %d accounts.',
            $this->organizations->writeTheSearchKeysAgain(),
            $this->persons->writeTheSearchKeysAgain(),
            $this->exchanges->writeTheKeysAgain(),
            $this->accounts->writeTheKeysAgain(),
        ));

        return Command::SUCCESS;
    }
}
