<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\CalendarGone;
use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;
use Prospecter\Core\Connectors\Domain\Port\ProviderCalendar;

/**
 * The calendars « Prospecter » that Google keeps, as the scenarios see them (ADR 0127). A calendar is created for a
 * token. It keeps one event per exchange. An event put again replaces itself, and an event may be removed. A token
 * that the provider refuses reaches nothing. A calendar that its member deleted is gone. When a scenario says so,
 * Google does not answer for a while, or refuses every event for good (ADR 0133).
 */
final class InMemoryProviderCalendar implements ProviderCalendar
{
    private const int BAD_REQUEST = 400;

    /** @var array<string, array<string, CalendarEvent>> each remaining calendar's events, by exchange */
    private array $calendars = [];
    private int $created = 0;
    /** @var list<string> the exchanges whose event was removed */
    private array $withdrawn = [];
    private bool $answering = true;
    private bool $acceptingEvents = true;
    /** @var array<string, string> the Meet links created, by exchange */
    private array $links = [];
    private int $linksAsked = 0;
    /** @var list<array{string, string}> what Google sent the guests: the notice, and to whom */
    private array $notices = [];

    public function __construct(private readonly InMemoryProviderAccess $access)
    {
    }

    public function create(ClientCredentials $client, RefreshToken $refreshToken): string
    {
        $this->ensureAnswering();
        $this->ensureGranted($client, $refreshToken);
        $calendar = sprintf('calendar-%d', ++$this->created);
        $this->calendars[$calendar] = [];

        return $calendar;
    }

    public function put(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, CalendarEvent $event): ?string
    {
        $this->ensureAnswering();
        $this->ensureGranted($client, $refreshToken);
        $this->ensureThere($calendar);
        $this->ensureAccepted($client);
        $this->tellTheGuestOf($event, $this->calendars[$calendar][$event->exchange->value] ?? null);
        $this->calendars[$calendar][$event->exchange->value] = $event;

        return $event->createsALink ? $this->conferenceOf($event->exchange) : null;
    }

    /**
     * As Google does for an event that invites someone, when the push asks it to tell them. Google sends the invitation
     * when they were not invited yet, and an update otherwise.
     */
    private function tellTheGuestOf(CalendarEvent $event, ?CalendarEvent $before): void
    {
        if (null === $event->guest || !$event->guest->isTold) {
            return;
        }

        $alreadyInvited = null !== $before?->guest && $before->guest->email->equals($event->guest->email);
        $this->notices[] = [$alreadyInvited ? 'update' : 'invitation', $event->guest->email->value];
    }

    /**
     * As Google does for a conference request that its exchange identifies. The conference is created once, and each
     * request gets it.
     */
    private function conferenceOf(ExchangeId $exchange): string
    {
        ++$this->linksAsked;

        return $this->links[$exchange->value] ??= sprintf('https://meet.google.com/%s', substr(md5($exchange->value), 0, 10));
    }

    public function remove(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void
    {
        $this->ensureAnswering();
        $this->ensureGranted($client, $refreshToken);
        $this->ensureThere($calendar);
        unset($this->calendars[$calendar][$exchange->value]);
        $this->withdrawn[] = $exchange->value;
    }

    public function cancel(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void
    {
        $guest = $this->calendars[$calendar][$exchange->value]->guest ?? null;
        $this->remove($client, $refreshToken, $calendar, $exchange);
        if (null !== $guest) {
            $this->notices[] = ['cancellation', $guest->email->value];
        }
    }

    /**
     * A temporary failure, thrown to be tried again, like a 5xx from Google.
     */
    private function ensureAnswering(): void
    {
        if (!$this->answering) {
            throw new \RuntimeException('Google does not answer');
        }
    }

    private function ensureAccepted(ClientCredentials $client): void
    {
        if (!$this->acceptingEvents) {
            throw CalendarRefusedThePush::by($client->provider, self::BAD_REQUEST);
        }
    }

    private function ensureGranted(ClientCredentials $client, RefreshToken $refreshToken): void
    {
        if (!$this->access->stillGrants($client, $refreshToken)) {
            throw AccessRevoked::by($client->provider);
        }
    }

    private function ensureThere(string $calendar): void
    {
        if (!isset($this->calendars[$calendar])) {
            throw CalendarGone::identifiedBy($calendar);
        }
    }

    public function stopAnswering(): void
    {
        $this->answering = false;
    }

    public function answerAgain(): void
    {
        $this->answering = true;
    }

    public function refuseEveryEvent(): void
    {
        $this->acceptingEvents = false;
    }

    public function acceptEveryEvent(): void
    {
        $this->acceptingEvents = true;
    }

    /**
     * As a member does in Google. The calendar goes, with its events.
     */
    public function deleteEveryCalendar(): void
    {
        $this->calendars = [];
    }

    /**
     * @return array<string, CalendarEvent> every remaining calendar's events, by exchange
     */
    public function events(): array
    {
        return array_merge([], ...array_values($this->calendars));
    }

    public function eventOf(string $exchange): ?CalendarEvent
    {
        return $this->events()[$exchange] ?? null;
    }

    public function linkCreatedFor(string $exchange): ?string
    {
        return $this->links[$exchange] ?? null;
    }

    /**
     * How many times a push asked Google for a conference.
     */
    public function linksAsked(): int
    {
        return $this->linksAsked;
    }

    /**
     * @return list<array{string, string}> each notice that Google sent a guest (`invitation`, `update` or `cancellation`), and to whom
     */
    public function noticesSent(): array
    {
        return $this->notices;
    }

    public function wasWithdrawn(string $exchange): bool
    {
        return in_array($exchange, $this->withdrawn, true);
    }
}
