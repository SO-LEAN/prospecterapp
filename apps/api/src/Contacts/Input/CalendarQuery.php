<?php

declare(strict_types=1);

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A period of the calendar, at the offset of its reader: the start of its first day, and the start of the day after
 * its last; and the clock of its reader, on which what is late is read (ADR 0134).
 */
final readonly class CalendarQuery
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'The start of its first day, included')]
        public ?\DateTimeImmutable $from = null,
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'The start of the day after its last, excluded; six weeks after `from` at most')]
        public ?\DateTimeImmutable $to = null,
        #[Assert\Timezone]
        #[OA\Property(description: 'The time zone of the reader, by its name in the database of the time zones; the offset of `from` otherwise', example: 'Europe/Paris')]
        public ?string $timeZone = null,
    ) {
    }

    public function start(): \DateTimeImmutable
    {
        return $this->from ?? throw new \LogicException('A validated period has a start');
    }

    public function end(): \DateTimeImmutable
    {
        return $this->to ?? throw new \LogicException('A validated period has an end');
    }

    /**
     * The moment on the clock of the reader.
     */
    public function read(\DateTimeImmutable $moment): \DateTimeImmutable
    {
        return $moment->setTimezone(null === $this->timeZone ? $this->from?->getTimezone() ?? new \DateTimeZone('UTC') : new \DateTimeZone($this->timeZone));
    }
}
