<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads the agenda of a member in `GSI1` of the links `DynamoExchangeRepository` writes while an exchange is
 * planned (`AGENDA#<member>`), without restoring the exchanges (ADR 0088).
 */
final readonly class DynamoAgendaRetriever implements AgendaRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function plannedBy(Prospector $holder, \DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        return $this->plannedAt($this->prospectingTable->queryBetween(ExchangeKeys::agenda($holder->id->value), Table::BY_GSI1, ExchangeKeys::positionAfter($from), ExchangeKeys::positionBefore($until)), $holder);
    }

    public function plannedIn(Prospector $holder, CalendarPeriod $period): array
    {
        return $this->plannedAt($this->prospectingTable->queryBetween(ExchangeKeys::agenda($holder->id->value), Table::BY_GSI1, ExchangeKeys::positionBefore($period->from), ExchangeKeys::positionBefore($period->until)), $holder);
    }

    public function firstPlannedFrom(Prospector $holder, \DateTimeImmutable $from, int $count): array
    {
        return $this->plannedAt($this->prospectingTable->queryAfter(ExchangeKeys::agenda($holder->id->value), Table::BY_GSI1, ExchangeKeys::positionBefore($from), $count), $holder);
    }

    /**
     * Read below the position of `until`, the latest first, then given back the oldest first.
     */
    public function plannedBefore(Prospector $holder, \DateTimeImmutable $until): array
    {
        return array_reverse($this->plannedAt($this->prospectingTable->query(ExchangeKeys::agenda($holder->id->value), Table::BY_GSI1, ExchangeKeys::positionBefore($until)), $holder));
    }

    /**
     * @param list<array<string, mixed>> $links
     *
     * @return list<ExchangeInTimeline>
     */
    private function plannedAt(array $links, Prospector $holder): array
    {
        $items = array_column($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], ExchangeKeys::EXCHANGE], $links)), null, 'PK');

        return array_values(array_map(
            static fn (array $link): ExchangeInTimeline => self::inTheAgenda($items[$link['SK']]['exchange'], $holder),
            array_filter($links, static fn (array $link): bool => isset($items[$link['SK']])),
        ));
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function inTheAgenda(array $document, Prospector $holder): ExchangeInTimeline
    {
        return DynamoTimelineRetriever::inTimeline($document, ContactPresence::ofTheContact(null !== $document['contact'], $document['contactForgotten'] ?? false), $holder);
    }
}
