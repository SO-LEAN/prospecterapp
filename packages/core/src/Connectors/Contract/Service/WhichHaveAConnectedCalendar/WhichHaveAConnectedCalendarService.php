<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

/**
 * The service Connectors offers the other contexts (ADR 0045, 0089): it refuses nothing, what is unknown is absent.
 */
interface WhichHaveAConnectedCalendarService
{
    public function __invoke(WhichHaveAConnectedCalendar $query, WhichHaveAConnectedCalendarOutput $output): void;
}
