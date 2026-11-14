<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultCalendar;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeIntent;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeSituation;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangePossibilities;
use Prospecter\Core\Prospecting\Domain\Port\Prospectors;
use Prospecter\Core\SharedKernel\Application\QueryHandler;
use Prospecter\Core\SharedKernel\Domain\MemberId;

final readonly class ConsultCalendarHandler implements QueryHandler
{
    public function __construct(
        private CalendarRetriever $calendarRetriever,
        private Prospectors $prospectors,
    ) {
    }

    public function __invoke(ConsultCalendar $query, ConsultCalendarOutput $output): void
    {
        $holder = $this->prospectors->prospectorFrom(new MemberId($query->prospectorId));
        $period = new CalendarPeriod($query->from, $query->until);

        $exchanges = $this->calendarRetriever->heldBy($holder, $period);

        $output->present(new Calendar($exchanges, $this->movableAmong($exchanges, $query->at)));
    }

    /**
     * Every exchange of the calendar is held by its reader: what it offers them is what its state offers its holder.
     *
     * @param list<ExchangeInTimeline> $exchanges
     *
     * @return list<string>
     */
    private function movableAmong(array $exchanges, \DateTimeImmutable $at): array
    {
        $movable = array_filter($exchanges, static fn (ExchangeInTimeline $exchange): bool => in_array(ExchangeIntent::MoveExchange, ExchangePossibilities::given(self::situationOf($exchange, $at)), true));

        return array_values(array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $movable));
    }

    private static function situationOf(ExchangeInTimeline $exchange, \DateTimeImmutable $at): ExchangeSituation
    {
        return new ExchangeSituation($exchange->attachmentCount, $exchange->toMakeConform, ExchangeStatus::from($exchange->status), $exchange->occurredAt <= $at, heldByTheMember: true, channel: Channel::from($exchange->channel));
    }
}
