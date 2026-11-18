<?php

namespace App\Shared;

use Bref\Context\Context;
use Bref\Event\Handler;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * The function the schedules of an instance call, each naming its task in its input (ADR 0154): every task run
 * without a member at its origin, the erasure of the contacts left unattached every hour (ADR 0146, 0147), the renewal
 * of the watches of Gmail every day.
 */
final readonly class ScheduledTasksFunction implements Handler
{
    /**
     * @param iterable<ScheduledTask> $tasks
     */
    public function __construct(
        #[AutowireIterator(ScheduledTask::TAG)]
        private iterable $tasks,
    ) {
    }

    /**
     * @return array<string, int> what the task did, counted
     */
    public function handle(mixed $event, Context $context): array
    {
        $name = is_array($event) && is_string($event['task'] ?? null) ? $event['task'] : '';

        return $this->taskNamed($name)->run();
    }

    private function taskNamed(string $name): ScheduledTask
    {
        foreach ($this->tasks as $task) {
            if ($name === $task->name()) {
                return $task;
            }
        }

        throw new \InvalidArgumentException(sprintf('No scheduled task "%s"', $name));
    }
}
