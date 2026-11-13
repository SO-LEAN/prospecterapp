<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\ConsultOverlappingExchanges;

/**
 * The exchanges the prospector planned that overlap a slot, before they plan another one in it; the duration, in
 * minutes, is half an hour when left out.
 */
final readonly class ConsultOverlappingExchanges
{
    public function __construct(
        public string $prospectorId,
        public \DateTimeImmutable $startsAt,
        public ?int $durationInMinutes = null,
    ) {
    }
}
