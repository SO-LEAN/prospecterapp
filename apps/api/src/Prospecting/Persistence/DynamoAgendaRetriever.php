<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
use Prospecter\Core\Prospecting\Domain\Model\CalendarPeriod;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;

/**
 * Reads a member's agenda in `GSI1`, from the links that `DynamoExchangeRepository` writes while an exchange is planned
 * (`AGENDA#<member>`). It does not restore the exchanges (ADR 0088).
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
     * Reads the exchanges below the position of `until`, the latest first, then returns them oldest first.
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
        $items = $this->prospectingTable->itemsInTheOrderOf(Typed::strings(array_column($links, 'SK')), ExchangeKeys::EXCHANGE);

        return array_map(static fn (array $item): ExchangeInTimeline => self::inTheAgenda(Typed::document($item['exchange']), $holder), $items);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function inTheAgenda(array $document, Prospector $holder): ExchangeInTimeline
    {
        return DynamoTimelineRetriever::inTimeline($document, ContactPresence::ofTheContact(null !== $document['contact'], Typed::bool($document['contactForgotten'] ?? false)), $holder);
    }
}
