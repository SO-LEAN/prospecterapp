<?php

namespace App\Campaigning\Resource;

use OpenApi\Attributes as OA;

/**
 * How far a campaign went (ADR 0145): its recipients processed by an exchange and those dismissed, none before it is
 * started.
 */
final readonly class CampaignProgressResource
{
    public function __construct(
        #[OA\Property(description: 'Its recipients processed by an exchange noted for them in it')]
        public int $processed,
        #[OA\Property(description: 'Its recipients dismissed without an exchange')]
        public int $dismissed,
    ) {
    }
}
