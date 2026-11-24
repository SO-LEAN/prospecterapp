<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * What a subscription does with an event it listens to: its policy's commands, sent to the command bus
 * (PolicyReaction).
 */
interface Reaction
{
    public function __invoke(object $event): void;
}
