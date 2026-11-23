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
 * Writes the aggregates' items again with the keys that the tables derive from them:
 *
 * - The organizations' and the persons' search keys (ADR 0071). It forgets the former search's items.
 * - The keys that list the exchanges by account, and the keys that list the accounts (ADR 0099).
 * - The links of the exchanges and of the to-dos in their holders' calendars (ADR 0115, 0134).
 * - The reservations of the contacts' links. When two contacts hold a link logged before links were unique, the oldest
 *   contact keeps it.
 * - The reservations of the organizations' links and website domains. Their LinkedIn profiles and pages come before the
 *   other links. Each link is recognized again by its address.
 *
 * Run it once on tables written before, then whenever an aggregate's key rules change. It can run any number of times.
 */
#[AsCommand(name: 'app:keys:rebuild', description: 'Rewrite the keys that the tables derive from the aggregates: the organizations\' and persons\' search keys, the keys that list the exchanges by account and the accounts, the exchanges\' and to-dos\' calendar links, the reservations of the contacts\' links and of the organizations\' links and website domains')]
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
