<?php

declare(strict_types=1);

namespace App\Shared\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The clock of the member reading a resource whose figures follow their day: the inactive periods of their booking
 * page ended before it are no longer shown (ADR 0134), the recipients of a campaign processed on it are counted.
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
