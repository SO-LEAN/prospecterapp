<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A task run without a member at its origin, every day, with the others (`ScheduledTasks`).
 */
#[AutoconfigureTag(self::TAG)]
interface ScheduledTask
{
    public const string TAG = 'app.scheduled_task';

    /**
     * The name of the task in what the scheduled tasks answer and log.
     */
    public function name(): string;

    /**
     * @return array<string, int> what the task did, counted
     */
    public function run(): array;
}
