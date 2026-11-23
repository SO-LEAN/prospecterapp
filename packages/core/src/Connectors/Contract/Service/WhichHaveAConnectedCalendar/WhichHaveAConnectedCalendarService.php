<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

/**
 * The service that Connectors offers the other contexts (ADR 0045, 0089). It refuses nothing. An unknown member is
 * absent from the answer.
 */
interface WhichHaveAConnectedCalendarService
{
    public function __invoke(WhichHaveAConnectedCalendar $query, WhichHaveAConnectedCalendarOutput $output): void;
}
