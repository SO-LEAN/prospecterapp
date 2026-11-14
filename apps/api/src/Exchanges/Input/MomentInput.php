<?php

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The new moment of a planned exchange, and its new duration if it changes, shaped like those of
 * `ExchangeDetailResource`.
 */
final readonly class MomentInput
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'When it is to happen now; in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[OA\Property(description: 'How long it lasts now, from 5 minutes to 8 hours; its own when left out')]
        public ?int $durationInMinutes = null,
    ) {
    }
}
