<?php

namespace App\Shared\Command;

use App\Shared\Persistence\FormerJournal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deletes the items of the former activity journal from the table of every context (ADR 0096): run once on each
 * instance, then removed.
 */
#[AsCommand(name: 'app:journal:purge', description: 'Delete the items of the former activity journal from the table of every context')]
final readonly class PurgeJournalCommand
{
    public function __construct(private FormerJournal $formerJournal)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Count the items of the journal without deleting them')]
        bool $dryRun = false,
    ): int {
        $items = $this->formerJournal->itemsByTable();

        if ($dryRun) {
            self::tell($items, 'to delete', $io);

            return Command::SUCCESS;
        }

        $this->formerJournal->delete($items);
        self::tell($items, 'deleted', $io);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, list<array{string, string}>> $itemsByTable
     */
    private static function tell(array $itemsByTable, string $fate, SymfonyStyle $io): void
    {
        foreach ($itemsByTable as $table => $items) {
            $io->writeln(sprintf('%s: %d items of the journal %s', $table, count($items), $fate));
        }

        $io->success(sprintf('%d items of the journal %s.', array_sum(array_map(count(...), $itemsByTable)), $fate));
    }
}
