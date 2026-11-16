<?php

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The clock its reader reads: a day without an hour, as a to-do is due, is read on it, never converted (ADR 0134).
 */
final readonly class ReaderClockQuery
{
    public function __construct(
        #[Assert\Timezone]
        #[OA\Property(description: 'The time zone of the reader, by its name in the database of the time zones; UTC otherwise', example: 'Europe/Paris')]
        public ?string $timeZone = null,
    ) {
    }

    /**
     * The moment on the clock of the reader.
     */
    public function read(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(new \DateTimeZone($this->timeZone ?? 'UTC'));
    }
}
