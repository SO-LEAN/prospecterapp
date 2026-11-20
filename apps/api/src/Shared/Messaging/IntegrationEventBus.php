<?php

namespace App\Shared\Messaging;

use Aws\EventBridge\EventBridgeClient;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;

/**
 * The bus of EventBridge the events travel on, from the relay to the subscriptions: an event goes as it travels,
 * its detail in JSON, ten at a time, the most a PutEvents takes, and under 256 KB, the least a PutEvents takes (1 MB
 * on AWS since January 2026, 256 KB before and on some buses): an e-mail imported carries its text (ADR 0154).
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
     * @throws EventsNotPublished when the bus refused some of them: those of the same put may have been published
     */
    public function publish(TransportedEvent ...$events): void
    {
        foreach ($this->putsOf(array_map(fn (TransportedEvent $event): array => ['event' => $event, 'entry' => $this->entryOf($event)], $events)) as $put) {
            $this->put($put);
        }
    }

    /**
     * The entries in their order, a put closed once the next one would make it too many or too big.
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
     * As EventBridge counts an entry: its source, its detail-type and its detail, in bytes.
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

        if ($result['FailedEntryCount'] > 0) {
            throw EventsNotPublished::refusedBy($this->name, $result['FailedEntryCount'], $events, $result['Entries']);
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
