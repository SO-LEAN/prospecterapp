<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

/**
 * What a subscription does with an event it listens to: a policy's commands sent to the command bus, or an event
 * handler of the core that is not a policy yet.
 */
interface Reaction
{
    public function __invoke(object $event): void;
}
