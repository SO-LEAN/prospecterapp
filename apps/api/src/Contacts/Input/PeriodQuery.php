<?php

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A period of the calendar, at the offset of its reader: the start of its first day, and the start of the day after
 * its last.
 */
final readonly class PeriodQuery
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'The start of its first day, included')]
        public ?\DateTimeImmutable $from = null,
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'The start of the day after its last, excluded; six weeks after `from` at most')]
        public ?\DateTimeImmutable $to = null,
    ) {
    }
}
