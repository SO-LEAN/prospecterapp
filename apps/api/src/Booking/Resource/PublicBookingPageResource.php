<?php

namespace App\Booking\Resource;

use OpenApi\Attributes as OA;

/**
 * A booking page as anyone holding its link reads it, without signing in: nothing of its host but that.
 */
final readonly class PublicBookingPageResource
{
    /**
     * @param list<string> $channels
     */
    public function __construct(
        #[OA\Property(description: 'Whether it offers slots; closed, it offers none')]
        public bool $open,
        #[OA\Property(type: 'array', items: new OA\Items(type: 'string', enum: ['call', 'videoCall']))]
        public array $channels,
    ) {
    }
}
