<?php

declare(strict_types=1);

namespace App\Shared\Messaging;

use App\Shared\Typed;
use Aws\EventBridge\EventBridgeClient;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

/**
 * The EventBridge bus that carries the events from the relay to the subscriptions. An event goes as it travels, with
 * its detail in JSON. A put holds ten entries at most, the most that PutEvents takes. A put also stays under 256 KB,
 * the lowest PutEvents limit (1 MB on AWS since January 2026, 256 KB before and on some buses). An imported e-mail
 * carries its text (ADR 0154).
 */
#[WithMonologChannel('messaging')]
final readonly class IntegrationEventBus
{
    private const int MOST_ENTRIES_OF_A_PUT = 10;
    private const int MOST_BYTES_OF_A_PUT = 256 * 1024;

    public function __construct(private EventBridgeClient $client, private string $name, private LoggerInterface $logger)
    {
    }

    /**
     * @throws EventsNotPublished when the bus refused some of them. The others in the same put may have been published.
     */
    public function publish(TransportedEvent ...$events): void
    {
        foreach ($this->putsOf(array_map(fn (TransportedEvent $event): array => ['event' => $event, 'entry' => $this->entryOf($event)], array_values($events))) as $put) {
            $this->put($put);
        }
    }

    /**
     * Splits the entries into puts, in order. A put closes when the next entry would make it hold too many entries or weigh too much.
     *
     * @param list<array{event: TransportedEvent, entry: array{Source: string, DetailType: string, Detail: string, EventBusName: string}}> $entries
     *
     * @return list<list<array{event: TransportedEvent, entry: array{Source: string, DetailType: string, Detail: string, EventBusName: string}}>>
     */
    private function putsOf(array $entries): array
    {
        $puts = [];
        $put = [];
        $bytes = 0;
        foreach ($entries as $entry) {
            $size = self::sizeOf($entry['entry']);
            if ([] !== $put && (self::MOST_ENTRIES_OF_A_PUT === count($put) || $bytes + $size > self::MOST_BYTES_OF_A_PUT)) {
                $puts[] = $put;
                [$put, $bytes] = [[], 0];
            }
            $put[] = $entry;
            $bytes += $size;
        }

        return [] === $put ? $puts : [...$puts, $put];
    }

    /**
     * The entry's size as EventBridge counts it: its source, detail-type and detail, in bytes.
     *
     * @param array{Source: string, DetailType: string, Detail: string, EventBusName: string} $entry
     */
    private static function sizeOf(array $entry): int
    {
        return strlen($entry['Source']) + strlen($entry['DetailType']) + strlen($entry['Detail']);
    }

    /**
     * @param list<array{event: TransportedEvent, entry: array{Source: string, DetailType: string, Detail: string, EventBusName: string}}> $put
     */
    private function put(array $put): void
    {
        $events = array_column($put, 'event');
        $result = $this->client->putEvents(['Entries' => array_column($put, 'entry')]);

        $refused = Typed::int($result['FailedEntryCount']);

        if ($refused > 0) {
            throw EventsNotPublished::refusedBy($this->name, $refused, $events, Typed::documents($result['Entries']));
        }

        foreach ($events as $event) {
            $this->logger->info('Event published on {bus}: {detailType} from {source}', ['bus' => $this->name, 'source' => $event->source, 'detailType' => $event->detailType, 'eventId' => $event->eventId, 'correlationId' => $event->correlationId]);
        }
    }

    /**
     * @return array{Source: string, DetailType: string, Detail: string, EventBusName: string}
     */
    private function entryOf(TransportedEvent $event): array
    {
        $entry = $event->toOutbox();

        return ['Source' => $entry['source'], 'DetailType' => $entry['detailType'], 'Detail' => json_encode($entry['detail'], \JSON_THROW_ON_ERROR), 'EventBusName' => $this->name];
    }
}
