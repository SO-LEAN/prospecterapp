<?php

declare(strict_types=1);

namespace App\Shared\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The clock of the member who reads a resource whose figures follow their day. Their booking page no longer shows the
 * inactive periods that ended before that day (ADR 0134). A campaign counts the recipients processed on that day.
 */
final readonly class MyClockQuery
{
    public function __construct(
        #[Assert\Timezone]
        #[OA\Property(description: 'The reader\'s time zone, by its name in the time zone database. UTC by default.', example: 'Europe/Paris')]
        public ?string $timeZone = null,
    ) {
    }
}
