<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Typed;

/**
 * The bus refused some events in a put, each with its reason. The whole batch must be published again.
 */
final class EventsNotPublished extends \RuntimeException
{
    /**
     * @param list<TransportedEvent>     $events
     * @param list<array<string, mixed>> $entries the bus's answer for each event, in order. A refused event has an ErrorCode and an ErrorMessage
     */
    public static function refusedBy(string $bus, int $refused, array $events, array $entries): self
    {
        $reasons = [];

        foreach ($entries as $position => $entry) {
            if (isset($entry['ErrorCode'])) {
                $reasons[] = sprintf('%s %s: %s %s', $events[$position]->detailType, $events[$position]->eventId, Typed::string($entry['ErrorCode']), Typed::stringOrNull($entry['ErrorMessage'] ?? null) ?? '');
            }
        }

        return new self(sprintf('The bus refused %d of %d events on %s: %s', $refused, count($events), $bus, implode('; ', $reasons)));
    }
}
