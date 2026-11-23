<?php

declare(strict_types=1);

namespace App\Shared;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Runs every task that no member starts, one after the other. An instance's daily schedule and the console run them. A
 * failing task is logged, and the next tasks still run. No schedule retries it: the next day's run makes up for it.
 */
final readonly class ScheduledTasks
{
    /**
     * @param iterable<ScheduledTask> $tasks
     */
    public function __construct(
        #[AutowireIterator(ScheduledTask::TAG)]
        private iterable $tasks,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Each task's counts by the task's name, and the names of the tasks that failed.
     *
     * @return array{ran: array<string, array<string, int>>, failed: list<string>}
     */
    public function run(): array
    {
        $ran = [];
        $failed = [];
        foreach ($this->tasks as $task) {
            $counts = $this->countsOf($task);
            if (null === $counts) {
                $failed[] = $task->name();
            } else {
                $ran[$task->name()] = $counts;
            }
        }

        return ['ran' => $ran, 'failed' => $failed];
    }

    /**
     * @return array<string, int>|null the task's counts, or null when it failed
     */
    private function countsOf(ScheduledTask $task): ?array
    {
        try {
            return $task->run();
        } catch (\Throwable $failure) {
            $this->logger->error('A scheduled task failed', ['task' => $task->name(), 'exception' => $failure]);

            return null;
        }
    }
}
