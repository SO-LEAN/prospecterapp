<?php

declare(strict_types=1);

namespace App\Shared;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * The function an instance's daily schedule calls. It runs every task that no member starts, among them the erasure of
 * unattached contacts (ADR 0146) and the renewal of the Gmail watches (ADR 0154). A failing task does not fail the
 * invocation. The function logs it and names it in the answer, which the function's logs keep.
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
