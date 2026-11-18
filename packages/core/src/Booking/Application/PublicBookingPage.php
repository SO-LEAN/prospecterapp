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
     * @param list<string>                                  $channels `call`, `videoCall`
     * @param list<array{day: string, slots: list<string>}> $days     the days from tomorrow on, on the clock of the host (YYYY-MM-DD), each with the moments its free slots start at (ISO 8601)
     */
    public function __construct(
        public bool $open,
        public array $channels,
        public array $days = [],
    ) {
    }

    public static function unavailable(): self
    {
        return new self(false, []);
    }
}
