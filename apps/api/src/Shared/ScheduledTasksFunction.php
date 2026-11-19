<?php

namespace App\Shared;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * The function the daily schedule of an instance calls: every task run without a member at its origin, the erasure of
 * the contacts left unattached (ADR 0146) and the renewal of the watches of Gmail (ADR 0154) among them. A task failing
 * fails not the invocation: logged, it is named in the answer, which the logs of the function keep.
 */
final readonly class ScheduledTasksFunction implements Handler
{
    public function __construct(private ScheduledTasks $tasks)
    {
    }

    /**
     * @return array{ran: array<string, array<string, int>>, failed: list<string>}
     */
    public function handle(mixed $event, Context $context): array
    {
        return $this->tasks->run();
    }
}
