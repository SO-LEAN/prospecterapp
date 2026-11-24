<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Agenda\ConsultOverlappingExchanges;

/**
 * Reads the exchanges the prospector planned that overlap a slot, before they plan another exchange in it. The
 * duration is in minutes. It is half an hour when the query gives none.
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
