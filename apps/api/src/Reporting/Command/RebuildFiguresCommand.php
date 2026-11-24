<?php

declare(strict_types=1);

namespace App\Reporting\Command;

use App\Reporting\AntiCorruption\AccountsSourceAccounts;
use App\Reporting\RebuildOfTheFigures;
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
 * It runs from the command line, outside a function's time limit. It writes only Reporting's table, whose figures
 * derive from the state. The worker may be recording, so each attempt waits for the recordings in flight (ADR 0106).
 *
 * It reports how many accounts the account list gives it. Zero is not a failure: a new instance has no account, and
 * app:consistency:check shows an account missing from that list.
 */
#[AsCommand(name: 'app:reporting:rebuild', description: 'Count every account\'s figures again from the other contexts\' state')]
final readonly class RebuildFiguresCommand
{
    public function __construct(private RebuildOfTheFigures $rebuild, private AccountsSourceAccounts $sourceAccounts)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $io->comment('Waiting five minutes for the facts being recorded, then counting every account again...');
        $this->rebuild->run($io);
        $io->success(sprintf('The figures are counted again for %d account(s).', iterator_count($this->sourceAccounts->all())));

        return Command::SUCCESS;
    }
}
