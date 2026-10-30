<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads the items of the table: a timeline
 * ordered by the moment of the exchanges then their identity, the most recent first, its cursor the moment and
 * the identity of the last exchange of the slice.
 */
final readonly class InMemoryTimelineRetriever implements TimelineRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function ofContact(PersonId $contact, Prospector $reader, ?string $after, int $max): Timeline
    {
        $withTheContact = array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => true === StateOf::the($exchange)['contact']?->equals($contact) && $exchange->accountId()->equals($reader->accountId));

        return self::timelineOf($withTheContact, $after, $max, 0, static fn (Exchange $exchange): ExchangeInTimeline => self::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)['contact'], StateOf::the($exchange)['contactForgotten']), $reader));
    }

    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline
    {
        return self::timelineOf($this->keptFor($organization), $after, $max, count($this->keptAndToMakeConform($organization)), self::inTheTimelineOf($organization, $reader));
    }

    public function toMakeConformIn(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline
    {
        $toMakeConform = $this->keptAndToMakeConform($organization);

        return self::timelineOf($toMakeConform, $after, $max, count($toMakeConform), self::inTheTimelineOf($organization, $reader));
    }

    /**
     * @return list<Exchange>
     */
    private function keptFor(OrganizationSheet $organization): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => array_any(StateOf::the($exchange)['keptFor'], static fn (OrganizationId $keptFor): bool => $keptFor->value === $organization->id)));
    }

    /**
     * @return list<Exchange>
     */
    private function keptAndToMakeConform(OrganizationSheet $organization): array
    {
        return array_values(array_filter($this->keptFor($organization), static fn (Exchange $exchange): bool => $exchange->isToMakeConform()));
    }

    /**
     * @return \Closure(Exchange): ExchangeInTimeline
     */
    private static function inTheTimelineOf(OrganizationSheet $organization, Prospector $reader): \Closure
    {
        return static function (Exchange $exchange) use ($organization, $reader): ExchangeInTimeline {
            $state = StateOf::the($exchange);
            $contact = $state['contact']?->value;
            $presence = ContactPresence::inTheOrganization(null !== $contact, $state['contactForgotten'], in_array($contact, $organization->contactIds, true));

            return self::inTimeline($exchange, $presence, $reader);
        };
    }

    /**
     * @param array<Exchange>                        $exchanges
     * @param \Closure(Exchange): ExchangeInTimeline $read
     */
    private static function timelineOf(array $exchanges, ?string $after, int $max, int $toMakeConform, \Closure $read): Timeline
    {
        $timeline = array_values(array_filter($exchanges, static fn (Exchange $exchange): bool => null === $after || strcmp(self::positionOf($exchange), $after) < 0));
        usort($timeline, static fn (Exchange $a, Exchange $b): int => strcmp(self::positionOf($b), self::positionOf($a)));
        $slice = array_slice($timeline, 0, $max);

        return new Timeline(
            array_map($read, $slice),
            count($timeline) > $max ? self::positionOf(array_last($slice)) : null,
            array_map(static fn (TimelineIntent $intent): string => $intent->name, TimelineIntent::cases()),
            $toMakeConform,
        );
    }

    private static function inTimeline(Exchange $exchange, ?ContactPresence $presence, Prospector $reader): ExchangeInTimeline
    {
        $state = StateOf::the($exchange);
        $facts = $state['facts'];
        $contactForgotten = $state['contactForgotten'];
        $declaredConform = null !== $state['madeConform'];

        return new ExchangeInTimeline(
            id: $exchange->id()->value,
            personId: $state['contact']?->value,
            contactPresence: $presence?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            missed: $facts->missed,
            occurredAt: $facts->occurredAt,
            text: $facts->text,
            textFlagged: ExchangeReading::isTextFlagged(null !== $facts->text, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform(null !== $facts->text, $contactForgotten, $declaredConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, $declaredConform, [] !== $exchange->attachments()),
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $state['keptFor']),
            attachmentCount: count($exchange->attachments()),
        );
    }

    private static function positionOf(Exchange $exchange): string
    {
        return StateOf::the($exchange)['facts']->occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z').'#'.$exchange->id();
    }
}
