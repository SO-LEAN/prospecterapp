<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

/**
 * Which of these members have their calendar connected (ADR 0089): asked about a set, a single member being a set
 * of one.
 */
final readonly class WhichHaveAConnectedCalendar
{
    /**
     * @param list<string> $memberIds
     */
    public function __construct(public array $memberIds)
    {
    }
}
