<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\CampaignId;
use Prospecter\Core\Prospecting\Domain\Model\Cancellation;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\ContactPresence;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeStatus;
use Prospecter\Core\Prospecting\Domain\Model\ImportedEmail;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Model\TimelineIntent;
use Prospecter\Core\Prospecting\Domain\Rule\ExchangeChronology;
use Prospecter\Core\Prospecting\Domain\Rule\ExchangeConformity;
use Prospecter\Core\Prospecting\Domain\Rule\ExchangeReading;
use Prospecter\Core\SharedKernel\Domain\Origin;

/**
 * Reads the exchanges that the in-memory repository saved, as the adapter reads the table's items. The timeline
 * sorts them by the moment they are listed at (`ExchangeChronology`), then by their id. Up to the reading moment,
 * the most recent comes first. After it, the soonest comes first. The cursor is the slice's last exchange: its
 * moment and its id.
 */
final readonly class InMemoryTimelineRetriever implements TimelineRetriever
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function ofContact(ContactId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        return self::timelineOf(self::until($asOf, $this->withContact($contact, $reader)), $after, $max, 0, self::inTheTimelineOfTheContact($reader));
    }

    public function ofOrganization(OrganizationSheet $organization, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
    {
        return self::timelineOf(self::until($asOf, $this->keptFor($organization)), $after, $max, count($this->keptAndToMakeConform($organization)), self::inTheTimelineOf($organization, $reader));
    }

    public function upcomingWithContact(ContactId $contact, Prospector $reader, \DateTimeImmutable $asOf, ?string $after, int $max): Timeline
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
    private function withContact(ContactId $contact, Prospector $reader): array
    {
        return array_values(array_filter($this->database->exchanges(), static fn (Exchange $exchange): bool => true === StateOf::the($exchange)->objectOrNull('contact', ContactId::class)?->equals($contact) && $exchange->accountId()->equals($reader->accountId)));
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
        return static fn (Exchange $exchange): ExchangeInTimeline => self::inTimeline($exchange, ContactPresence::ofTheContact(null !== StateOf::the($exchange)->objectOrNull('contact', ContactId::class), StateOf::the($exchange)->bool('contactForgotten')), $reader);
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
            $contact = $state->objectOrNull('contact', ContactId::class)?->value;
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
     * Lists the upcoming exchanges after the cursor, the soonest first.
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
            personId: $state->objectOrNull('contact', ContactId::class)?->value,
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
     * @param list<Exchange> $slice holds fewer exchanges than the list
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
