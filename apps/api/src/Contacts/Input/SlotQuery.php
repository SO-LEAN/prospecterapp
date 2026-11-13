<?php

namespace App\Contacts\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A slot of the agenda: when it starts, and how long it lasts.
 */
final readonly class SlotQuery
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time')]
        public ?\DateTimeImmutable $startsAt = null,
        #[OA\Property(description: 'From 5 minutes to 8 hours; 30 when left out')]
        public ?int $durationInMinutes = null,
    ) {
    }
}
