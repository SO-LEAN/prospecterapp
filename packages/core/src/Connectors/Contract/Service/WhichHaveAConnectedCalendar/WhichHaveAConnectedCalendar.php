<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

/**
 * Asks which of these members have their calendar connected (ADR 0089). The question is about a set of members. A
 * single member is a set of one.
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
