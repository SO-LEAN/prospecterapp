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
 * Port: the calendar « Prospecter » the provider keeps for a member, reached with their token through the client of
 * their account (ADR 0127), implemented by the API. An event is identified by its exchange: put twice, it is one.
 * A failure that may pass is thrown as it is, to be tried again.
 */
interface ProviderCalendar
{
    /**
     * @return string the identity of the calendar created
     *
     * @throws AccessRevoked|CalendarRefusedThePush
     */
    public function create(ClientCredentials $client, RefreshToken $refreshToken): string;

    /**
     * The event created, or put in place of the one of its exchange, brought back if it was removed; asking a link,
     * the conference of the event created once, whatever the number of pushes (ADR 0137).
     *
     * @return string|null the link of the conference of an event asking one, none otherwise
     *
     * @throws AccessRevoked|CalendarGone|CalendarRefusedThePush
     */
    public function put(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, CalendarEvent $event): ?string;

    /**
     * The event of the exchange removed, nothing done when there is none.
     *
     * @throws AccessRevoked|CalendarGone|CalendarRefusedThePush
     */
    public function remove(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void;

    /**
     * The event of the exchange removed, the provider telling its guest it is cancelled; nothing done when there is
     * none.
     *
     * @throws AccessRevoked|CalendarGone|CalendarRefusedThePush
     */
    public function cancel(ClientCredentials $client, RefreshToken $refreshToken, string $calendar, ExchangeId $exchange): void;
}
