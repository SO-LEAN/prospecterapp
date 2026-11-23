<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\BookingPage\OfferChannels;

final readonly class OfferChannels
{
    /**
     * @param list<string> $channels `call`, `videoCall`
     */
    public function __construct(
        public string $memberId,
        public array $channels,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
