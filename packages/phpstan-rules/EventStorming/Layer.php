<?php

declare(strict_types=1);

namespace Prospecter\PHPStan\EventStorming;

/**
 * A layer of a context the calls are followed through, and what they look for there: the application reaches the
 * intentions of the aggregates, the domain reaches the events they record.
 */
enum Layer: string
{
    case Application = 'Application';
    case Domain = 'Domain';

    public function reaches(ClassName $target, Call $call): bool
    {
        return match ($this) {
            self::Application => ($target->isAnAggregate() && !$call->isCreation()) || ($target->isAnEvent() && $call->isCreation()),
            self::Domain => $target->isAnEvent() && $call->isCreation(),
        };
    }

    public function goesThrough(ClassName $target): bool
    {
        return $this->value === $target->layer();
    }
}
