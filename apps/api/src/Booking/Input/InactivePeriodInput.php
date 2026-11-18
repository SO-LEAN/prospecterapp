<?php

namespace App\Booking\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class InactivePeriodInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Date]
        #[OA\Property(format: 'date', example: '2026-12-21')]
        public ?string $firstDay = null,
        #[Assert\NotBlank]
        #[Assert\Date]
        #[OA\Property(format: 'date', example: '2027-01-04')]
        public ?string $lastDay = null,
    ) {
    }
}
