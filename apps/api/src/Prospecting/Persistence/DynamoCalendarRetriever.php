<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\CalendarRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads a member's calendar in `GSI1`, from the links that `DynamoExchangeRepository` writes with every exchange
 * (`CALENDAR#<member>`). One query between two positions reads the period. It does not restore the exchanges (ADR
 * 0088).
 */
final readonly class DynamoCalendarRetriever implements CalendarRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function heldBy(Prospector $holder, CalendarPeriod $period): array
    {
        $links = $this->prospectingTable->queryBetween(ExchangeKeys::calendar($holder->id->value), Table::BY_GSI1, ExchangeKeys::positionBefore($period->from), ExchangeKeys::positionBefore($period->until));
        $items = $this->prospectingTable->itemsInTheOrderOf(Typed::strings(array_column($links, 'SK')), ExchangeKeys::EXCHANGE);

        return array_map(static fn (array $item): ExchangeInTimeline => self::inTheCalendar(Typed::document($item['exchange']), $holder), $items);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function inTheCalendar(array $document, Prospector $holder): ExchangeInTimeline
    {
        return DynamoTimelineRetriever::inTimeline($document, ContactPresence::ofTheContact(null !== $document['contact'], Typed::bool($document['contactForgotten'] ?? false)), $holder);
    }
}
