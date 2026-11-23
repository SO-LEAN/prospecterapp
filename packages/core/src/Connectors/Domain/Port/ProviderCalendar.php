<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Port;

use Prospecter\Core\Connectors\Domain\Exception\AccessRevoked;
use Prospecter\Core\Connectors\Domain\Exception\CalendarGone;
use Prospecter\Core\Connectors\Domain\Exception\CalendarRefusedThePush;
use Prospecter\Core\Connectors\Domain\Model\CalendarEvent;
use Prospecter\Core\Connectors\Domain\Model\ClientCredentials;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\RefreshToken;

/**
 * Port: the calendar « Prospecter » that the provider keeps for a member (ADR 0127). Connectors reaches it with the
 * member's token, through their account's client. The API implements this port. An event is identified by its
 * exchange, so an event put twice is still one event. A transient failure is thrown as it is, so that it is tried
 * again.
 */
interface ProviderCalendar
{
    /**
     * @return string the created calendar's id
     *
     * @throws AccessRevoked|CalendarRefusedThePush
     */
    public function create(ClientCredentials $client, RefreshToken $refreshToken): string;

    /**
     * Creates the event, or replaces its exchange's event. An event that was removed is brought back. When the event
     * asks for a link, its conference is created once, however many times it is pushed (ADR 0137).
     *
     * @return string|null the conference link of an event that asks for one, none otherwise
     *
     * @throws AccessRevoked|CalendarGone|CalendarRefusedThePush
     */
    public function put(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, CalendarEvent $event): ?string;

    /**
     * Removes the exchange's event. Does nothing when there is none.
     *
     * @throws AccessRevoked|CalendarGone|CalendarRefusedThePush
     */
    public function remove(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void;

    /**
     * Removes the exchange's event, and the provider tells its guest that it is cancelled. Does nothing when there is
     * none.
     *
     * @throws AccessRevoked|CalendarGone|CalendarRefusedThePush
     */
    public function cancel(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void;
}
