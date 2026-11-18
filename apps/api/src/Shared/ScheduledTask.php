<?php

namespace App\Shared;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * A task run without a member at its origin, by a schedule of an instance naming it (ADR 0147, 0154).
 */
#[AutoconfigureTag(self::TAG)]
interface ScheduledTask
{
    public const string TAG = 'app.scheduled_task';

    /**
     * The name a schedule gives the task in the input of the function.
     */
    public function name(): string;

    /**
     * @return array<string, int> what the task did, counted
     */
    public function run(): array;
}
