<?php

declare(strict_types=1);

namespace App\Reporting\Command;

use App\Reporting\AntiCorruption\AccountsSourceAccounts;
use App\Shared\Persistence\ConditionFailed;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Counts every account's figures again from the other contexts' state, in a new version that becomes the active one
 * (ADR 0103).
 *
 * Run it once when Reporting goes into service on an instance, after `app:keys:rebuild` has written the keys that list
 * the accounts and an account's exchanges in the older items. Run it again whenever the figures go wrong.
 *
 * It runs from the command line, outside a function's time limit. Its steps commit their own units of work, so it
 * calls its handler without a command's unit of work. It writes only Reporting's table, whose figures derive from the
 * state.
 *
 * A worker's recording on the same figures can cancel a step. The command then starts the rebuild again, three times
 * at most. Each new attempt first abandons the version that the cancelled one was building, then empties it (ADR 0104,
 * 0106).
 *
 * It reports how many accounts the account list gives it. Zero is not a failure: a new instance has no account, and
 * app:consistency:check shows an account missing from that list.
 */
#[AsCommand(name: 'app:reporting:rebuild', description: 'Count every account\'s figures again from the other contexts\' state')]
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

                $io->warning('A fact recorded during the rebuild cancelled it. Starting it again.');
            }
        }
    }
}
