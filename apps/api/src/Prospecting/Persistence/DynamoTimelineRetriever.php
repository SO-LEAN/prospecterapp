<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the timelines in the indexes `DynamoExchangeRepository` writes (`ExchangeKeys`), without restoring the
 * exchanges (ADR 0088): the timeline of a contact in `GSI1` of the exchanges, that of an organization in `GSI1`
 * of its links, those to make conform in `GSI2`, counted there (ADR 0074, 0076, 0079).
 */
final readonly class DynamoTimelineRetriever implements TimelineRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function ofContact(PersonId $contact, Prospector $reader, ?string $after, int $max): Timeline
    {
        $items = $this->prospectingTable->query(ExchangeKeys::timeline($reader->accountId->value, $contact->value), Table::BY_GSI1, $after, $max + 1);

        return $this->timelineOf($items, $max, 0, static fn (array $document): ExchangeInTimeline => self::inTimeline($document, ContactPresence::ofTheContact(null !== $document['contact'], self::contactForgotten($document)), $reader));
    }

    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline
    {
        $links = $this->prospectingTable->query(ExchangeKeys::organizationTimeline($organization->id), Table::BY_GSI1, $after, $max + 1);

        return $this->timelineOf($this->itemsListedBy($links), $max, $this->countToMakeConform($organization), self::inTheTimelineOf($organization, $reader));
    }

    public function toMakeConformIn(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline
    {
        $links = $this->prospectingTable->query(ExchangeKeys::toMakeConform($organization->id), Table::BY_GSI2, $after, $max + 1);

        return $this->timelineOf($this->itemsListedBy($links), $max, $this->countToMakeConform($organization), self::inTheTimelineOf($organization, $reader));
    }

    private function countToMakeConform(OrganizationSheet $organization): int
    {
        return $this->prospectingTable->count(ExchangeKeys::toMakeConform($organization->id), Table::BY_GSI2);
    }

    /**
     * The items of the exchanges the links name, in the order of the links.
     *
     * @param list<array<string, mixed>> $links
     *
     * @return list<array<string, mixed>>
     */
    private function itemsListedBy(array $links): array
    {
        $byKey = array_column($this->prospectingTable->items(array_map(static fn (array $link): array => [$link['SK'], ExchangeKeys::EXCHANGE], $links)), null, 'PK');

        return array_values(array_filter(array_map(static fn (array $link): ?array => $byKey[$link['SK']] ?? null, $links)));
    }

    /**
     * @param list<array<string, mixed>>                         $items one more than a slice tells whether another follows
     * @param \Closure(array<string, mixed>): ExchangeInTimeline $read
     */
    private function timelineOf(array $items, int $max, int $toMakeConform, \Closure $read): Timeline
    {
        $slice = Documents::slice(array_column($items, 'exchange'), $max, ExchangeKeys::positionOf(...));

        return new Timeline(
            array_map($read, $slice->content),
            $slice->next,
            array_map(static fn (TimelineIntent $intent): string => $intent->name, TimelineIntent::cases()),
            $toMakeConform,
        );
    }

    /**
     * @return \Closure(array<string, mixed>): ExchangeInTimeline
     */
    private static function inTheTimelineOf(OrganizationSheet $organization, Prospector $reader): \Closure
    {
        return static function (array $document) use ($organization, $reader): ExchangeInTimeline {
            $contact = $document['contact'];
            $presence = ContactPresence::inTheOrganization(null !== $contact, self::contactForgotten($document), in_array($contact, $organization->contactIds, true));

            return self::inTimeline($document, $presence, $reader);
        };
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function inTimeline(array $document, ?ContactPresence $presence, Prospector $reader): ExchangeInTimeline
    {
        $facts = $document['facts'];
        $hasText = null !== $facts['text'];
        $contactForgotten = self::contactForgotten($document);
        $declaredConform = null !== ($document['madeConform'] ?? null);
        $attachments = $document['attachments'] ?? [];

        return new ExchangeInTimeline(
            id: $document['id'],
            personId: $document['contact'],
            contactPresence: $presence?->value,
            channel: $facts['channel'],
            direction: $facts['direction'],
            missed: $facts['missed'],
            occurredAt: Documents::momentOf($facts['occurredAt']),
            text: $facts['text'],
            textFlagged: ExchangeReading::isTextFlagged($hasText, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform($hasText, $contactForgotten, $declaredConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, $declaredConform, [] !== $attachments),
            organizationIds: $document['keptFor'],
            attachmentCount: count($attachments),
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function contactForgotten(array $document): bool
    {
        return $document['contactForgotten'] ?? false;
    }
}
