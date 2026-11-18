<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar;

interface WhichHaveAConnectedCalendarOutput
{
    /**
     * @param list<string> $memberIds those, among the members asked, whose calendar is connected; a member without
     *                                a connection, or whose connection is to reconnect, is absent
     */
    public function present(array $memberIds): void;
}
