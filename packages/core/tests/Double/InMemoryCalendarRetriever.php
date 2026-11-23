<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * Reads the exchanges that the in-memory repository saved, as the adapter reads a prospector's calendar. The
 * calendar holds the exchanges the prospector holds that happen in the period, whatever their status. It sorts
 * them by their moment, then by their id.
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

            return $state->object('createdBy', MemberId::class)->equals($holder->id) && $state->object('facts', ExchangeFacts::class)->occurredAt >= $period->from && $state->object('facts', ExchangeFacts::class)->occurredAt < $period->until;
        }));
        usort($held, static fn (Exchange $a, Exchange $b): int => [StateOf::the($a)->object('facts', ExchangeFacts::class)->occurredAt, (string) $a->id()] <=> [StateOf::the($b)->object('facts', ExchangeFacts::class)->occurredAt, (string) $b->id()]);

        return array_map(static fn (Exchange $exchange): ExchangeInTimeline => InMemoryTimelineRetriever::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)->objectOrNull('contact', ContactId::class), StateOf::the($exchange)->bool('contactForgotten')), $holder), $held);
    }
}
