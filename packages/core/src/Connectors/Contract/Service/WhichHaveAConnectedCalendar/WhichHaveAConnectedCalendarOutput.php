<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

interface WhichHaveAConnectedCalendarOutput
{
    /**
     * @param list<string> $memberIds the members asked whose calendar is connected. A member without a connection
     *                                is absent. So is a member whose connection is to reconnect.
     */
    public function present(array $memberIds): void;
}
