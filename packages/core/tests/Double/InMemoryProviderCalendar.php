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
 * The calendars « Prospecter » Google keeps, as the scenarios see them (ADR 0127): created for a token, an event by
 * its exchange, put again in place of itself, removed; a token the provider refuses reaches nothing, a calendar its
 * member deleted is gone; Google not answering a while, or refusing every event for good, as a scenario says (ADR 0133).
 */
final class InMemoryProviderCalendar implements ProviderCalendar
{
    private const int BAD_REQUEST = 400;

    /** @var array<string, array<string, CalendarEvent>> the events of each calendar still there, by exchange */
    private array $calendars = [];
    private int $created = 0;
    /** @var list<string> the exchanges whose event was removed */
    private array $withdrawn = [];
    private bool $answering = true;
    private bool $acceptingEvents = true;
    /** @var array<string, string> the Meet links created, by exchange */
    private array $links = [];
    private int $linksAsked = 0;

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

    public function put(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, CalendarEvent $event): void
    {
        $this->ensureAnswering();
        $this->ensureGranted($client, $refreshToken);
        $this->ensureThere($calendar);
        $this->ensureAccepted($client);
        $this->calendars[$calendar][$event->exchange->value] = $event;
    }

    public function remove(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void
    {
        $this->ensureAnswering();
        $this->ensureGranted($client, $refreshToken);
        $this->ensureThere($calendar);
        unset($this->calendars[$calendar][$exchange->value]);
        $this->withdrawn[] = $exchange->value;
    }

    /**
     * A failure that may pass, thrown as it is to be tried again, as a 5xx of Google.
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
     * As a member does in Google: the calendar goes with its events.
     */
    public function deleteEveryCalendar(): void
    {
        $this->calendars = [];
    }

    /**
     * @return array<string, CalendarEvent> the events of every calendar still there, by exchange
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

    public function wasWithdrawn(string $exchange): bool
    {
        return in_array($exchange, $this->withdrawn, true);
    }
}
