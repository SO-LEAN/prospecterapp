<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\Port\AgendaRetriever;
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
        $links = $this->prospectingTable->queryBetween(ExchangeKeys::agenda($holder->id->value), Table::BY_GSI1, ExchangeKeys::positionAfter($from), ExchangeKeys::positionBefore($until));
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
