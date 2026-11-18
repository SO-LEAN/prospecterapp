<?php

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Days the host may not be booked, both bounds included.
 */
final readonly class InactivePeriodResource
{
    public function __construct(
        public string $id,
        #[OA\Property(format: 'date', example: '2026-12-21')]
        public string $firstDay,
        #[OA\Property(format: 'date', example: '2027-01-04')]
        public string $lastDay,
        #[SerializedName('_links')]
        public InactivePeriodLinksResource $links,
    ) {
    }
}
