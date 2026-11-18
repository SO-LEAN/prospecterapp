<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\SetWeeklyWindows;

final readonly class SetWeeklyWindows
{
    /**
     * @param list<array{weekday: string, from: string, to: string}> $windows  each day once at most, its times HH:MM
     * @param string                                                 $timeZone the IANA name of the clock of the host
     */
    public function __construct(
        public string $memberId,
        public array $windows,
        public string $timeZone,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
