<?php

namespace App\Me\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Whether I am reminded of the exchanges I planned, and how many minutes before; changed as a whole by PATCH.
 */
final readonly class RemindersResource
{
    public function __construct(
        public bool $enabled,
        #[OA\Property(enum: [0, 5, 10, 15, 30, 60])]
        public int $minutesBefore,
        #[SerializedName('_links')]
        public RemindersLinksResource $links,
    ) {
    }
}
