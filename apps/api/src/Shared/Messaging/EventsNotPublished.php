<?php

namespace App\Shared\Messaging;

/**
 * The bus refused some events of a put, each with its reason: the whole batch is to be published again.
 */
final class EventsNotPublished extends \RuntimeException
{
    /**
     * @param list<TransportedEvent>                                                   $events
     * @param list<array{ErrorCode?: string, ErrorMessage?: string, EventId?: string}> $entries the answer of the bus for each event, in order
     */
    public static function refusedBy(string $bus, int $refused, array $events, array $entries): self
    {
        $reasons = [];

        foreach ($entries as $position => $entry) {
            if (isset($entry['ErrorCode'])) {
                $reasons[] = sprintf('%s %s: %s %s', $events[$position]->detailType, $events[$position]->eventId, $entry['ErrorCode'], $entry['ErrorMessage'] ?? '');
            }
        }

        return new self(sprintf('The bus refused %d of %d events on %s: %s', $refused, count($events), $bus, implode('; ', $reasons)));
    }
}
