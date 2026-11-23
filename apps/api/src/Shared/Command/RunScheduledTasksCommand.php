<?php

declare(strict_types=1);

namespace App\Shared\Command;

use App\Shared\ScheduledTasks;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Runs every scheduled task, as an instance's daily schedule does. Run it by hand on the local stack, because its
 * MiniStack runs no schedule. Run it on an instance whose schedule missed a day. Each task also keeps its own command.
 */
#[AsCommand(name: 'app:scheduled-tasks:run', description: 'Run every scheduled task, as an instance\'s daily schedule does')]
final readonly class RunScheduledTasksCommand
{
    public function __construct(private ScheduledTasks $tasks)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $run = $this->tasks->run();
        $io->listing(array_map(self::described(...), array_keys($run['ran']), $run['ran']));
        foreach ($run['failed'] as $task) {
            $io->error(sprintf('%s failed: its exception is in the log', $task));
        }

        return [] === $run['failed'] ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param array<string, int> $counts
     */
    private static function described(string $task, array $counts): string
    {
        $counted = array_map(static fn (string $what, int $count): string => sprintf('%s %d', $what, $count), array_keys($counts), $counts);

        return sprintf('%s: %s', $task, implode(', ', $counted));
    }
}
