<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Slot;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultOverlappingExchangesHandler implements QueryHandler
{
    public function __construct(
        private AgendaRetriever $agendaRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultOverlappingExchanges $query, ConsultOverlappingExchangesOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $slot = new Slot($query->startsAt, Duration::inMinutes($query->durationInMinutes));
        $planned = $this->agendaRetriever->plannedBy($holder, $slot->earliestStartOfAnOverlap(), $slot->end());

        $output->present(new OverlappingExchanges(array_values(array_filter($planned, static fn (ExchangeInTimeline $exchange): bool => $slot->overlaps(self::slotOf($exchange))))));
    }

    private static function slotOf(ExchangeInTimeline $exchange): Slot
    {
        return new Slot($exchange->occurredAt, Duration::inMinutes($exchange->durationInMinutes));
    }
}
