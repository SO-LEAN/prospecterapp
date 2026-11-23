<?php

declare(strict_types=1);

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A planned exchange's new moment, and its new duration if it changes. Both have the same shape as in
 * `ExchangeDetailResource`.
 */
final readonly class MomentInput
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'When it is now to happen, in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
        #[OA\Property(description: 'How long it now lasts, from 5 minutes to 8 hours. It keeps its duration when left out')]
        public ?int $durationInMinutes = null,
    ) {
    }
}
