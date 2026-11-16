<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Event;

/**
 * A video call received the link the calendar of its holder created for it, by its holder (ADR 0137).
 */
final readonly class VideoCallLinkReceived extends ExchangeEvent
{
}
