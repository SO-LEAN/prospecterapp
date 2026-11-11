<?php

namespace App\Consistency;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Checks the result rather than watching the failures, on demand, reading only: the figures of Reporting counted again
 * from the tables of the other contexts, what the reactions between the contexts should have written, the keys listing
 * the accounts and the exchanges of each, and the queues of the failures. Lists each discrepancy with its account, what it concerns and the command correcting it, and fails
 * when there is one (ADR 0105).
 */
#[AsCommand(name: 'app:consistency:check', description: 'Check that the figures, the reactions between the contexts and the lists of the items agree with the tables, and that no failure waits')]
final readonly class CheckConsistencyCommand
{
    private const array COLUMNS = ['Account', 'Concerns', 'Discrepancy', 'Corrected by'];

    public function __construct(
        private ContextTables $tables,
        private FiguresAgainstTheTables $figures,
        private ReactionsBetweenContexts $reactions,
        private KeysListingTheItems $keysListingTheItems,
        private FailureQueues $failureQueues,
    ) {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'The id of the only account to check (the queues of the failures are checked whatever it is)')] ?string $account = null): int
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
            ...$this->figures->discrepanciesAmong($accountIds),
            ...$this->reactions->discrepanciesAmong($accountIds),
            ...$this->keysListingTheItems->discrepanciesAmong($accountIds),
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
