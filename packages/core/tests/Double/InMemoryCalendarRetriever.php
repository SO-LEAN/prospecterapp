<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads the calendar of a prospector: those they
 * hold, whatever their status, happening in the period, by their moment then their id.
 */
final readonly class InMemoryCalendarRetriever implements CalendarRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function heldBy(Prospector $holder, CalendarPeriod $period): array
    {
        $held = array_values(array_filter($this->database->exchanges(), static function (Exchange $exchange) use ($holder, $period): bool {
            $state = StateOf::the($exchange);

            return $state->value('createdBy')->equals($holder->id) && $state->value('facts')->occurredAt >= $period->from && $state->value('facts')->occurredAt < $period->until;
        }));
        usort($held, static fn (Exchange $a, Exchange $b): int => [StateOf::the($a)->value('facts')->occurredAt, (string) $a->id()] <=> [StateOf::the($b)->value('facts')->occurredAt, (string) $b->id()]);

        return array_map(static fn (Exchange $exchange): ExchangeInTimeline => InMemoryTimelineRetriever::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)->value('contact'), StateOf::the($exchange)->value('contactForgotten')), $holder), $held);
    }
}
