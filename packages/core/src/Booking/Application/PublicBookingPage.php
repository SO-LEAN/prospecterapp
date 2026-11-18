<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * A booking page as anyone holding its link reads it, without signing in: whether it offers slots, and how its host
 * meets the bookers. Nothing of the host but that.
 */
final readonly class PublicBookingPage
{
    /**
     * @param list<string> $channels `call`, `videoCall`
     */
    public function __construct(
        public bool $open,
        public array $channels,
    ) {
    }
}
