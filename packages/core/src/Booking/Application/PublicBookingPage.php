<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application;

/**
 * A booking page as anyone with its link reads it, without signing in: who the booker meets, how, and the host's free
 * slots on the host's clock (ADR 0153). A page that offers nothing shows nothing, and does not say why.
 */
final readonly class PublicBookingPage
{
    /**
     * @param list<string>                                  $channels `call`, `videoCall`
     * @param ?string                                       $timeZone the host's clock, on which the days and the slots are given
     * @param list<array{day: string, slots: list<string>}> $days     the days from tomorrow, on the host's clock (YYYY-MM-DD), each with the start times of its free slots (ISO 8601)
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
