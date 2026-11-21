<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads the agenda of a prospector: those they
 * planned, by their moment then their id, the soonest first.
 */
final readonly class InMemoryAgendaRetriever implements AgendaRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function plannedBy(Prospector $holder, \DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        return $this->planned($holder, static fn (\DateTimeImmutable $at): bool => $at > $from && $at < $until);
    }

    public function plannedIn(Prospector $holder, CalendarPeriod $period): array
    {
        return $this->planned($holder, static fn (\DateTimeImmutable $at): bool => $at >= $period->from && $at < $period->until);
    }

    public function firstPlannedFrom(Prospector $holder, \DateTimeImmutable $from, int $count): array
    {
        return array_slice($this->planned($holder, static fn (\DateTimeImmutable $at): bool => $at >= $from), 0, $count);
    }

    public function plannedBefore(Prospector $holder, \DateTimeImmutable $until): array
    {
        return $this->planned($holder, static fn (\DateTimeImmutable $at): bool => $at < $until);
    }

    /**
     * @param \Closure(\DateTimeImmutable): bool $happensWhenAsked
     *
     * @return list<ExchangeInTimeline>
     */
    private function planned(Prospector $holder, \Closure $happensWhenAsked): array
    {
        $planned = array_values(array_filter($this->database->exchanges(), static function (Exchange $exchange) use ($holder, $happensWhenAsked): bool {
            $state = StateOf::the($exchange);

            return ExchangeStatus::Planned === $state->value('status') && $state->value('createdBy')->equals($holder->id) && $happensWhenAsked($state->value('facts')->occurredAt);
        }));
        usort($planned, static fn (Exchange $a, Exchange $b): int => [StateOf::the($a)->value('facts')->occurredAt, (string) $a->id()] <=> [StateOf::the($b)->value('facts')->occurredAt, (string) $b->id()]);

        return array_map(static fn (Exchange $exchange): ExchangeInTimeline => InMemoryTimelineRetriever::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)->value('contact'), StateOf::the($exchange)->value('contactForgotten')), $holder), $planned);
    }
}
