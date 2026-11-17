<?php

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
 * Records again the facts the tags of the contacts derive from, as Prospecting and PersonalData give them from their
 * state (ADR 0141): once Campaigns derives them on an instance, then whenever `app:consistency:check` finds them out
 * of date. Run from the command line, out of the time limit of a function; each fact commits its own unit, so it
 * calls its handler without the unit of work of a command. It writes only the table of Campaigns, and starts again
 * when a recording of the worker on the same fact cancels it, three times at most: the facts replace each other by
 * their revision, nothing waits.
 */
#[AsCommand(name: 'app:tags:rebuild', description: 'Record again the facts the tags of the contacts derive from, for every account or one')]
final readonly class RebuildTagsCommand
{
    private const int RETRIES_OF_A_CONFLICT = 3;

    public function __construct(private RebuildTagsHandler $rebuildTags, private AccountsTaggedAccounts $taggedAccounts)
    {
    }

    public function __invoke(SymfonyStyle $io, #[Option(description: 'The account to rebuild, every account without it')] ?string $account = null): int
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
