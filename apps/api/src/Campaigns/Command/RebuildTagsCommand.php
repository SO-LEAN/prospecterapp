<?php

declare(strict_types=1);

namespace App\Campaigns\Command;

use App\Campaigns\AntiCorruption\AccountsTaggedAccounts;
use App\Shared\Persistence\ConditionFailed;
use Prospecter\Core\Campaigns\Application\RebuildTags\RebuildTags;
use Prospecter\Core\Campaigns\Application\RebuildTags\RebuildTagsHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Records again the facts that the contacts' tags are derived from, as Prospecting and PersonalData give them from
 * their state (ADR 0141). Run it once when Campaigns starts deriving them on an instance, then whenever
 * `app:consistency:check` finds them out of date. It runs from the command line, outside a function's time limit. Each
 * fact commits its own unit of work, so the command calls its handler without a command's unit of work. It writes only
 * Campaigns' table. When the worker records the same fact and cancels the write, it retries, three times at most. The
 * facts replace each other by revision, so nothing waits.
 */
#[AsCommand(name: 'app:tags:rebuild', description: 'Record again the facts that the contacts\' tags are derived from, for every account or for one')]
final readonly class RebuildTagsCommand
{
    private const int RETRIES_OF_A_CONFLICT = 3;

    public function __construct(private RebuildTagsHandler $rebuildTags, private AccountsTaggedAccounts $taggedAccounts)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'The account to rebuild. Without it, every account is rebuilt')] ?string $account = null): int
    {
        $this->rebuildStartingAgainOnAConflict($io, $account);
        $io->success(sprintf('The tags are rebuilt for %d account(s).', null === $account ? iterator_count($this->taggedAccounts->all()) : 1));

        return Command::SUCCESS;
    }

    private function rebuildStartingAgainOnAConflict(SymfonyStyle $io, ?string $account): void
    {
        for ($retry = 1;; ++$retry) {
            try {
                ($this->rebuildTags)(new RebuildTags($account));

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
