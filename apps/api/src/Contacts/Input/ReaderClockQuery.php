<?php

declare(strict_types=1);

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The reader's clock. A day without an hour, such as a to-do's due date, is read on this clock and never converted
 * (ADR 0134).
 */
final readonly class ReaderClockQuery
{
    public function __construct(
        #[Assert\Timezone]
        #[OA\Property(description: 'The reader\'s time zone, by its name in the time zone database. Without it, UTC applies', example: 'Europe/Paris')]
        public ?string $timeZone = null,
    ) {
    }

    /**
     * Gives the moment on the reader's clock.
     */
    public function read(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone($this->timeZone ?? 'UTC'));
    }
}
