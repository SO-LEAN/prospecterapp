<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\WhichHaveAConnectedCalendar;

use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendar;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarOutput;
use Prospecter\Core\Connectors\Contract\Service\WhichHaveAConnectedCalendar\WhichHaveAConnectedCalendarService;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\ConnectionRepository;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

/**
 * A calendar is connected while the connection of its member to Google is: one to reconnect reaches nothing.
 */
final readonly class WhichHaveAConnectedCalendarHandler implements QueryHandler, WhichHaveAConnectedCalendarService
{
    public function __construct(private ConnectionRepository $connections)
    {
    }

    public function __invoke(WhichHaveAConnectedCalendar $query, WhichHaveAConnectedCalendarOutput $output): void
    {
        $output->present(array_values(array_filter(
            $query->memberIds,
            fn (string $member): bool => true === $this->connections->find(new MemberId($member), Provider::Google)?->isConnected(),
        )));
    }
}
