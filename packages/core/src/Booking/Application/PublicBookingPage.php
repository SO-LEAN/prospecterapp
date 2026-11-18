<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * A booking page as anyone holding its link reads it, without signing in: whom the booker meets, how, and the free
 * slots of its host on their clock (ADR 0153). A page offering nothing says nothing, not even why.
 */
final readonly class PublicBookingPage
{
    /**
     * @param list<string>                                  $channels `call`, `videoCall`
     * @param ?string                                       $timeZone the clock of the host, which the days and the slots are on
     * @param list<array{day: string, slots: list<string>}> $days     the days from tomorrow on, on the clock of the host (YYYY-MM-DD), each with the moments its free slots start at (ISO 8601)
     */
    public function __construct(
        public bool $open,
        public ?PublicHost $host,
        public array $channels,
        public ?string $timeZone,
        public array $days,
    ) {
    }

    public static function unavailable(): self
    {
        return new self(false, null, [], null, []);
    }
}
