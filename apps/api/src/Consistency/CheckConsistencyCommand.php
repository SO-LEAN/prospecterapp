<?php

declare(strict_types=1);

namespace App\Consistency;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Checks the result on demand, rather than watching the failures, and only reads. It checks:
 *
 * - Reporting's figures, counted again from the other contexts' tables.
 * - What the reactions between the contexts should have written.
 * - The facts that the contacts' tags derive from (ADR 0141).
 * - The keys that list the accounts, and each account's exchanges.
 * - The upcoming exchanges in the calendars of their holders connected to a provider (ADR 0133).
 * - The contacts that hold the same link.
 * - The failure queues.
 *
 * It lists each discrepancy with its account, what it is about and the command that corrects it. The lists'
 * discrepancies come first, because their keys must be written before the figures are counted again. It fails when it
 * finds one (ADR 0105).
 */
#[AsCommand(name: 'app:consistency:check', description: 'Check that the figures, the reactions between the contexts and the item lists agree with the tables, and that no failure waits')]
final readonly class CheckConsistencyCommand
{
    private const array COLUMNS = ['Account', 'Concerns', 'Discrepancy', 'Corrected by'];

    public function __construct(
        private ContextTables $tables,
        private FiguresAgainstTheTables $figures,
        private ReactionsBetweenContexts $reactions,
        private TagsAgainstTheTables $tags,
        private KeysListingTheItems $keysListingTheItems,
        private FailureQueues $failureQueues,
        private ExchangesInTheCalendars $exchangesInTheCalendars,
        private ReferencesOfTheContacts $references,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'The id of the only account to check (the failure queues are checked in any case)')] ?string $account = null): int
    {
        if (null !== $account && !$this->tables->isAnAccount($account)) {
            $io->error(sprintf('No account %s.', $account));

            return Command::INVALID;
        }

        $discrepancies = $this->discrepanciesAmong(null === $account ? $this->tables->accounts() : [$account]);

        if ([] === $discrepancies) {
            $io->success('No discrepancy: the figures, the reactions and the lists agree with the tables, no failure waits.');

            return Command::SUCCESS;
        }

        $io->table(self::COLUMNS, array_map(self::rowOf(...), $discrepancies));
        $io->error(sprintf('%d discrepancies; correct them by the command of each, then check again.', count($discrepancies)));

        return Command::FAILURE;
    }

    /**
     * @param list<string> $accountIds
     *
     * @return list<Discrepancy>
     */
    private function discrepanciesAmong(array $accountIds): array
    {
        return [
            ...$this->keysListingTheItems->discrepanciesAmong($accountIds),
            ...$this->figures->discrepanciesAmong($accountIds),
            ...$this->reactions->discrepanciesAmong($accountIds),
            ...$this->tags->discrepanciesAmong($accountIds),
            ...$this->exchangesInTheCalendars->discrepanciesAmong($accountIds),
            ...$this->references->discrepanciesAmong($accountIds),
            ...$this->failureQueues->discrepancies(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function rowOf(Discrepancy $discrepancy): array
    {
        return [$discrepancy->account ?? '', $discrepancy->concerns, $discrepancy->description, $discrepancy->correction->value];
    }
}
