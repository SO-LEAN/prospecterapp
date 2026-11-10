<?php

namespace App\Shared\Messaging;

use Aws\EventBridge\EventBridgeClient;
use Psr\Log\LoggerInterface;

/**
 * The bus of EventBridge the events travel on, from the relay to the subscriptions: an event goes as it travels,
 * its detail in JSON, ten at a time, the most a PutEvents takes.
 */
final readonly class IntegrationEventBus
{
    private const int MOST_ENTRIES_OF_A_PUT = 10;

    public function __construct(private EventBridgeClient $client, private string $name, private LoggerInterface $logger)
    {
    }

    /**
     * @throws EventsNotPublished when the bus refused some of them: those of the same put may have been published
     */
    public function publish(TransportedEvent ...$events): void
    {
        foreach (array_chunk($events, self::MOST_ENTRIES_OF_A_PUT) as $put) {
            $this->put($put);
        }
    }

    /**
     * @param list<TransportedEvent> $events
     */
    private function put(array $events): void
    {
        $result = $this->client->putEvents(['Entries' => array_map($this->entryOf(...), $events)]);

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
