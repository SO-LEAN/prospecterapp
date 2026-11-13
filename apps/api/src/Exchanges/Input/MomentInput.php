<?php

namespace App\Exchanges\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The new moment of a planned exchange, shaped like the moment of `ExchangeDetailResource`.
 */
final readonly class MomentInput
{
    public function __construct(
        #[Assert\NotNull]
        #[OA\Property(format: 'date-time', description: 'When it is to happen now; in the future')]
        public ?\DateTimeImmutable $occurredAt = null,
    ) {
    }
}
