<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads the agenda of a prospector: those they
 * planned, by their moment, the soonest first.
 */
final readonly class InMemoryAgendaRetriever implements AgendaRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function plannedBy(Prospector $holder, \DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        $planned = array_values(array_filter($this->database->exchanges(), static function (Exchange $exchange) use ($holder, $from, $until): bool {
            $state = StateOf::the($exchange);

            return ExchangeStatus::Planned === $state['status'] && $state['createdBy']->equals($holder->id) && $state['facts']->occurredAt > $from && $state['facts']->occurredAt < $until;
        }));
        usort($planned, static fn (Exchange $a, Exchange $b): int => StateOf::the($a)['facts']->occurredAt <=> StateOf::the($b)['facts']->occurredAt);

        return array_map(static fn (Exchange $exchange): ExchangeInTimeline => InMemoryTimelineRetriever::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)['contact'], StateOf::the($exchange)['contactForgotten']), $holder), $planned);
    }
}
