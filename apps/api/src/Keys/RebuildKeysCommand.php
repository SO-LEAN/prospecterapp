<?php

declare(strict_types=1);

namespace App\Keys;

use App\Accounts\Persistence\DynamoAccountRepository;
use App\PersonalData\Persistence\DynamoPersonRepository;
use App\Prospecting\Persistence\DynamoExchangeRepository;
use App\Prospecting\Persistence\DynamoOrganizationRepository;
use App\Prospecting\Persistence\DynamoToDoRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Writes again the items of the aggregates with the keys the tables derive from them: the search keys of the
 * organizations and of the persons (ADR 0071), forgetting the items of the former search, the keys listing the
 * exchanges by account and the keys listing the accounts (ADR 0099), the links of the exchanges and of the to-dos in
 * the calendars of their holders (ADR 0115, 0134), the reservations of the links the contacts hold, one two contacts
 * noted before it was unique for the oldest, and of the links and the domains of the websites of the organizations,
 * their LinkedIn profiles and pages before the links taken as links, each recognized again by its address: once on
 * tables written before, then whenever
 * the keys of an aggregate change of rule. It can run any number of times.
 */
#[AsCommand(name: 'app:keys:rebuild', description: 'Write again the keys the tables derive from the aggregates: search keys of the organizations and of the persons, keys listing the exchanges by account and the accounts, links of the exchanges and of the to-dos in the calendars, reservations of the links of the contacts, of the links and of the domains of the websites of the organizations')]
final readonly class RebuildKeysCommand
{
    public function __construct(
        private DynamoOrganizationRepository $organizations,
        private DynamoPersonRepository $persons,
        private DynamoExchangeRepository $exchanges,
        private DynamoAccountRepository $accounts,
        private DynamoToDoRepository $toDos,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->success(sprintf(
            'Wrote the keys of %d organizations, %d persons, %d exchanges, %d accounts and %d to-dos.',
            $this->organizations->writeTheKeysAgain(),
            $this->persons->writeTheKeysAgain(),
            $this->exchanges->writeTheKeysAgain(),
            $this->accounts->writeTheKeysAgain(),
            $this->toDos->writeTheKeysAgain(),
        ));

        return Command::SUCCESS;
    }
}
