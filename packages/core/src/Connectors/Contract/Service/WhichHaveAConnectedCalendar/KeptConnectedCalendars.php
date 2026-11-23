<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

/**
 * Keeps the answer as it is. Its client reads the answer and does not show it (ADR 0085).
 */
final class KeptConnectedCalendars implements WhichHaveAConnectedCalendarOutput
{
    /**
     * @var list<string>
     */
    public private(set) array $memberIds = [];

    /**
     * @param list<string> $memberIds
     */
    public function present(array $memberIds): void
    {
        $this->memberIds = $memberIds;
    }
}
