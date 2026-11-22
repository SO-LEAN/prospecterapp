<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeChronology;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Policy\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Origin;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Reads the exchanges the in-memory repository saved, as the adapter reads the items of the table: a timeline
 * ordered by the moment the exchanges are listed at (`ExchangeChronology`) then their identity, the most recent first until the moment of the
 * reading, the soonest first after it, its cursor the moment and the identity of the last exchange of the slice.
 */
final readonly class InMemoryTimelineRetriever implements TimelineRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function ofContact(PersonId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        return self::timelineOf(self::until($asOf, $this->withContact($contact, $reader)), $after, $max, 0, self::inTheTimelineOfTheContact($reader));
    }

    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        return self::timelineOf(self::until($asOf, $this->keptFor($organization)), $after, $max, count($this->keptAndToMakeConform($organization)), self::inTheTimelineOf($organization, $reader));
    }

    public function upcomingWithContact(PersonId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        return self::upcomingOf(self::after($asOf, $this->withContact($contact, $reader)), $after, $max, 0, self::inTheTimelineOfTheContact($reader));
    }

    public function upcomingInOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        return self::upcomingOf(self::after($asOf, $this->keptFor($organization)), $after, $max, count($this->keptAndToMakeConform($organization)), self::inTheTimelineOf($organization, $reader));
    }

    public function toMakeConformIn(OrganizationSheet $organization, Prospector $reader, ?string $after, int $max): Timeline
    {
        $toMakeConform = $this->keptAndToMakeConform($organization);

        return self::timelineOf($toMakeConform, $after, $max, count($toMakeConform), self::inTheTimelineOf($organization, $reader));
    }

    /**
     * @return list<Exchange>
     */
    private function withContact(PersonId $contact, Prospector $reader): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => true === StateOf::the($exchange)->objectOrNull('contact', PersonId::class)?->equals($contact) && $exchange->accountId()->equals($reader->accountId)));
    }

    /**
     * @param list<Exchange> $exchanges
     *
     * @return list<Exchange>
     */
    private static function until(\DateTimeImmutable $asOf, array $exchanges): array
    {
        return array_values(array_filter($exchanges, static fn (Exchange $exchange): bool => self::listedAt($exchange) <= $asOf));
    }

    /**
     * Those after the moment.
     *
     * @param list<Exchange> $exchanges
     *
     * @return list<Exchange>
     */
    private static function after(\DateTimeImmutable $asOf, array $exchanges): array
    {
        return array_values(array_filter($exchanges, static fn (Exchange $exchange): bool => self::listedAt($exchange) > $asOf));
    }

    /**
     * @return \Closure(Exchange): ExchangeInTimeline
     */
    private static function inTheTimelineOfTheContact(Prospector $reader): \Closure
    {
        return static fn (Exchange $exchange): ExchangeInTimeline => self::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)->objectOrNull('contact', PersonId::class), StateOf::the($exchange)->bool('contactForgotten')), $reader);
    }

    /**
     * @return list<Exchange>
     */
    private function keptFor(OrganizationSheet $organization): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => array_any(StateOf::the($exchange)->listOf('keptFor', OrganizationId::class), static fn (OrganizationId $keptFor): bool => $keptFor->value === $organization->id)));
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
            $contact = $state->objectOrNull('contact', PersonId::class)?->value;
            $presence = ContactPresence::inTheOrganization(null !== $contact, $state->bool('contactForgotten'), in_array($contact, $organization->contactIds, true));

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
            count($timeline) > $max ? self::positionOf(self::lastOf($slice)) : null,
            array_map(static fn (TimelineIntent $intent): string => $intent->name, TimelineIntent::cases()),
            $toMakeConform,
        );
    }

    /**
     * The soonest first, after the cursor.
     *
     * @param array<Exchange>                        $exchanges
     * @param \Closure(Exchange): ExchangeInTimeline $read
     */
    private static function upcomingOf(array $exchanges, ?string $after, int $max, int $toMakeConform, \Closure $read): Timeline
    {
        $upcoming = array_values(array_filter($exchanges, static fn (Exchange $exchange): bool => null === $after || strcmp(self::positionOf($exchange), $after) > 0));
        usort($upcoming, static fn (Exchange $a, Exchange $b): int => strcmp(self::positionOf($a), self::positionOf($b)));
        $slice = array_slice($upcoming, 0, $max);

        return new Timeline(
            array_map($read, $slice),
            count($upcoming) > $max ? self::positionOf(self::lastOf($slice)) : null,
            array_map(static fn (TimelineIntent $intent): string => $intent->name, TimelineIntent::cases()),
            $toMakeConform,
        );
    }

    public static function inTimeline(Exchange $exchange, ?ContactPresence $presence, Prospector $reader): ExchangeInTimeline
    {
        $state = StateOf::the($exchange);
        $facts = $state->object('facts', ExchangeFacts::class);
        $contactForgotten = $state->bool('contactForgotten');
        $declaredConform = null !== $state->objectOrNull('madeConform', Correction::class);

        return new ExchangeInTimeline(
            id: $exchange->id()->value,
            personId: $state->objectOrNull('contact', PersonId::class)?->value,
            contactPresence: $presence?->value,
            channel: $facts->channel->value,
            direction: $facts->direction->value,
            occurredAt: $facts->occurredAt,
            status: $state->object('status', ExchangeStatus::class)->value,
            canceller: $state->objectOrNull('cancellation', Cancellation::class)?->canceller?->value,
            durationInMinutes: $facts->duration?->minutes,
            text: $facts->text,
            place: $facts->place,
            link: $facts->link?->url,
            textFlagged: ExchangeReading::isTextFlagged(null !== $facts->text, $reader),
            textToMakeConform: ExchangeConformity::isTextToMakeConform(null !== $facts->text, $contactForgotten, $declaredConform),
            toMakeConform: ExchangeConformity::isToMakeConform($contactForgotten, $declaredConform, [] !== $exchange->attachments()),
            organizationIds: array_map(static fn (OrganizationId $organization): string => $organization->value, $state->listOf('keptFor', OrganizationId::class)),
            attachmentCount: count($exchange->attachments()),
            campaignId: $state->objectOrNull('campaign', CampaignId::class)?->value,
            withAContactToAttach: null !== $exchange->contactToAttach(),
            bookedByLink: $exchange->isBookedByLink(),
            importedFrom: $state->objectOrNull('importedFrom', ImportedEmail::class)?->provider,
            createdByAnAI: Origin::Ai === $state->object('origin', Origin::class),
        );
    }

    private static function listedAt(Exchange $exchange): \DateTimeImmutable
    {
        $state = StateOf::the($exchange);

        return ExchangeChronology::listedAt($state->object('facts', ExchangeFacts::class)->occurredAt, $state->objectOrNull('cancellation', Cancellation::class)?->at);
    }

    /**
     * @param list<Exchange> $slice shorter than the exchanges listed
     */
    private static function lastOf(array $slice): Exchange
    {
        return array_last($slice) ?? throw new \LogicException('A slice shorter than the exchanges listed is not empty');
    }

    private static function positionOf(Exchange $exchange): string
    {
        return self::listedAt($exchange)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z').'#'.$exchange->id();
    }
}
