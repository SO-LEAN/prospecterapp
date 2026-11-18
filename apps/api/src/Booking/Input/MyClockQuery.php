<?php

namespace App\Booking\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The clock of the host reading their page: the inactive periods ended before its day are no longer shown (ADR 0134).
 */
final readonly class MyClockQuery
{
    public function __construct(
        #[Assert\Timezone]
        #[OA\Property(description: 'The time zone of the reader, by its name in the database of the time zones; UTC otherwise', example: 'Europe/Paris')]
        public ?string $timeZone = null,
    ) {
    }
}
