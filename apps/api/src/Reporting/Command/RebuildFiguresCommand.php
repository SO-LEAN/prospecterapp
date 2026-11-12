<?php

namespace App\Reporting\Command;

use App\Reporting\AntiCorruption\AccountsSourceAccounts;
use App\Shared\Persistence\ConditionFailed;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Counts the figures of every account again, from the state of the other contexts, in a new version made the active
 * one (ADR 0103): once Reporting is put in service on an instance, after `app:keys:rebuild` wrote the keys listing
 * the accounts and the exchanges of an account in the items written before them, then whenever its figures went
 * wrong. Run from the command line, out of the time limit of a function; its steps commit their own units, so it
 * calls its handler without the unit of work of a command. It writes only the table of Reporting, figures derived
 * from the state. A step cancelled by a recording of the worker on the same figures starts the rebuild again, three
 * times at most, the version it was building abandoned then emptied first (ADR 0104, 0106). It says how many accounts
 * the list of the accounts gives it, none being no failure: a new instance has none, and app:consistency:check shows
 * an account missing from that list.
 */
#[AsCommand(name: 'app:reporting:rebuild', description: 'Count the figures of every account again, from the state of the other contexts')]
final readonly class RebuildFiguresCommand
{
    private const int RETRIES_OF_A_CONFLICT = 3;

    public function __construct(private RebuildFiguresHandler $rebuildFigures, private AccountsSourceAccounts $sourceAccounts)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->rebuildStartingAgainOnAConflict($io);
        $io->success(sprintf('The figures are counted again for %d account(s).', iterator_count($this->sourceAccounts->all())));

        return Command::SUCCESS;
    }

    private function rebuildStartingAgainOnAConflict(SymfonyStyle $io): void
    {
        for ($retry = 1;; ++$retry) {
            $io->comment('Waiting two minutes for the facts being recorded, then counting every account again...');

            try {
                ($this->rebuildFigures)(new RebuildFigures());

                return;
            } catch (ConditionFailed $conflict) {
                if ($retry > self::RETRIES_OF_A_CONFLICT) {
                    throw $conflict;
                }

                $io->warning('A fact recorded meanwhile cancelled the rebuild: starting it again.');
            }
        }
    }
}
