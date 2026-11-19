<?php

namespace App\Shared;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Every task run without a member at its origin, one after the other: what the daily schedule of an instance runs, and
 * the console. A task failing is logged and the following ones run: no schedule retries, the next day makes up.
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
     * What each task counted, by its name, and the names of those that failed.
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
     * @return array<string, int>|null what the task counted, none when it failed
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
