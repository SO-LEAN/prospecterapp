<?php

declare(strict_types=1);

namespace App\Me\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Whether I am reminded of my planned exchanges, and how many minutes before. A PATCH changes them as a whole.
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
