<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultExchangesToProcess;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\HolderIntents;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Slot;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultExchangesToProcessHandler implements QueryHandler
{
    public function __construct(
        private AgendaRetriever $agendaRetriever,
        private Prospectors $prospectors,
    ) {
    }

    /**
     * The agenda holds only what is still planned: one started is to process once it has ended.
     */
    public function __invoke(ConsultExchangesToProcess $query, ConsultExchangesToProcessOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));

        $started = $this->agendaRetriever->plannedBefore($holder, $query->at);
        $ended = array_values(array_filter($started, static fn (ExchangeInTimeline $exchange): bool => self::slotOf($exchange)->hasEndedBy($query->at)));

        $output->present(new ExchangesToProcess($ended, HolderIntents::offeredBy($ended, $query->at)));
    }

    private static function slotOf(ExchangeInTimeline $exchange): Slot
    {
        return new Slot($exchange->occurredAt, Duration::inMinutes($exchange->durationInMinutes));
    }
}
