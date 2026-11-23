<?php

declare(strict_types=1);

namespace App\Shared;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A task that no member starts. It runs every day, with the others (`ScheduledTasks`).
 */
#[AutoconfigureTag(self::TAG)]
interface ScheduledTask
{
    public const string TAG = 'app.scheduled_task';

    /**
     * The task's name in the scheduled tasks' answer and logs.
     */
    public function name(): string;

    /**
     * @return array<string, int> the counts of what the task did
     */
    public function run(): array;
}
