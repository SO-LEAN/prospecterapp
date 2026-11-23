<?php

declare(strict_types=1);

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use App\Shared\Typed;
use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Reads the timelines in the indexes that `DynamoExchangeRepository` writes (`ExchangeKeys`). It does not restore the
 * exchanges (ADR 0088). A contact's timeline is in the exchanges' `GSI1`. An organization's timeline is in its links'
 * `GSI1`. The exchanges to redact are in `GSI2`, which also counts them (ADR 0074, 0076, 0079). The past is below the
 * reading moment's position, the most recent first. What is to come is above it, the soonest first.
 */
final readonly class DynamoTimelineRetriever implements TimelineRetriever
{
    public function __construct(private Table $prospectingTable)
    {
    }

    public function ofContact(ContactId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        $items = $this->prospectingTable->query(ExchangeKeys::timeline($reader->accountId->value, $contact->value), Table::BY_GSI1, $after ?? ExchangeKeys::positionAfter($asOf), $max + 1);

        return $this->timelineOf($items, $max, 0, self::inTheTimelineOfTheContact($reader));
    }

    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        $links = $this->prospectingTable->query(ExchangeKeys::organizationTimeline($organization->id), Table::BY_GSI1, $after ?? ExchangeKeys::positionAfter($asOf), $max + 1);

        return $this->timelineOf($this->itemsListedBy($links), $max, $this->countToMakeConform($organization), self::inTheTimelineOf($organization, $reader));
    }

    public function upcomingWithContact(ContactId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        $items = $this->prospectingTable->queryAfter(ExchangeKeys::timeline($reader->accountId->value, $contact->value), Table::BY_GSI1, $after ?? ExchangeKeys::positionAfter($asOf), $max + 1);

        return $this->timelineOf($items, $max, 0, self::inTheTimelineOfTheContact($reader));
    }

    public function upcomingInOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        $links = $this->prospectingTable->queryAfter(ExchangeKeys::organizationTimeline($organization->id), Table::BY_GSI1, $after ?? ExchangeKeys::positionAfter($asOf), $max + 1);

        return $this->timelineOf($this->itemsListedBy($links), $max, $this->countToMakeConform($organization), self::inTheTimelineOf($organization, $reader));
    }

    public function toMakeConformIn(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline
    {
        $links = $this->prospectingTable->query(ExchangeKeys::toRedact($organization->id), Table::BY_GSI2, $after, $max + 1);

        return $this->timelineOf($this->itemsListedBy($links), $max, $this->countToMakeConform($organization), self::inTheTimelineOf($organization, $reader));
    }

    private function countToMakeConform(OrganizationSheet $organization): int
    {
        return $this->prospectingTable->count(ExchangeKeys::toRedact($organization->id), Table::BY_GSI2);
    }

    /**
     * Reads the items of the exchanges that the links name, in the links' order.
     *
     * @param list<array<string, mixed>> $links
     *
     * @return list<array<string, mixed>>
     */
    private function itemsListedBy(array $links): array
    {
        return $this->prospectingTable->itemsInTheOrderOf(Typed::strings(array_column($links, 'SK')), ExchangeKeys::EXCHANGE);
    }

    /**
     * @param list<array<string, mixed>>                         $items a slice and one more item, which tells whether another slice follows
     * @param \Closure(array<string, mixed>): ExchangeInTimeline $read
     */
    private function timelineOf(array $items, int $max, int $toMakeConform, \Closure $read): Timeline
    {
        $slice = Documents::slice(Typed::documents(array_column($items, 'exchange')), $max, ExchangeKeys::positionOf(...));

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
    private static function inTheTimelineOfTheContact(Prospector $reader): \Closure
    {
        return static function (array $exchange) use ($reader): ExchangeInTimeline {
            $document = Typed::document($exchange);

            return self::inTimeline($document, ContactPresence::ofTheContact(null !== $document['contact'], self::contactForgotten($document)), $reader);
        };
    }

    /**
     * @return \Closure(array<string, mixed>): ExchangeInTimeline
     */
    private static function inTheTimelineOf(OrganizationSheet $organization, Prospector $reader): \Closure
    {
        return static function (array $exchange) use ($organization, $reader): ExchangeInTimeline {
            $document = Typed::document($exchange);
            $contact = Typed::stringOrNull($document['contact']);
            $presence = ContactPresence::inTheOrganization(null !== $contact, self::contactForgotten($document), in_array($contact, $organization->contactIds, true));

            return self::inTimeline($document, $presence, $reader);
        };
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function inTimeline(array $document, ?ContactPresence $presence, Prospector $reader): ExchangeInTimeline
    {
        $facts = Typed::document($document['facts']);
        $text = Typed::stringOrNull($facts['text']);
        $contact = Typed::stringOrNull($document['contact']);
        $keptFor = Typed::strings($document['keptFor']);
        $contactForgotten = self::contactForgotten($document);
        $declaredConform = null !== ($document['redacted'] ?? null);
        $attachments = Typed::array($document['attachments'] ?? []);
        $importedFrom = $document['importedFrom'] ?? null;

        return new ExchangeInTimeline(
            id: Typed::string($document['id']),
            personId: $contact,
            contactPresence: $presence?->value,
            channel: Typed::string($facts['channel']),
            direction: Typed::string($facts['direction']),
            occurredAt: Documents::momentOf(Typed::string($facts['occurredAt'])),
            status: ExchangeDocument::statusOf($document)->value,
            canceller: ExchangeDocument::cancellerOf($document),
            durationInMinutes: ExchangeDocument::durationInMinutesOf($facts),
            text: $text,
            place: Typed::stringOrNull($facts['place'] ?? null),
            link: Typed::stringOrNull($facts['link'] ?? null),
            textFlagged: ExchangeReading::isTextFlagged(null !== $text, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform(null !== $text, $contactForgotten, $declaredConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, $declaredConform, [] !== $attachments),
            organizationIds: $keptFor,
            attachmentCount: count($attachments),
            campaignId: Typed::stringOrNull($document['campaignId'] ?? null),
            withAContactToAttach: Exchange::isWithAContactToAttach(null !== $contact, [] !== $keptFor),
            bookedByLink: Typed::bool($document['bookedByLink'] ?? false),
            importedFrom: null === $importedFrom ? null : Typed::string(Typed::document($importedFrom)['provider']),
            createdByAnAI: Origin::Ai === Documents::originOf($document),
        );
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function contactForgotten(array $document): bool
    {
        return Typed::bool($document['contactForgotten'] ?? false);
    }
}
