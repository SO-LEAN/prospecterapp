<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A video call received the link that its holder's calendar created for it. Its holder is the event's author (ADR
 * 0137).
 */
final readonly class VideoCallLinkReceived extends ExchangeEvent
{
}
