<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use OpenApi\Attributes as OA;

/**
 * How far a campaign has gone (ADR 0145): its recipients that an exchange processed, and those dismissed. Both are 0
 * before the campaign starts.
 */
final readonly class CampaignProgressResource
{
    public function __construct(
        #[OA\Property(description: 'Its recipients processed by an exchange logged for them in the campaign')]
        public int $processed,
        #[OA\Property(description: 'Its recipients dismissed without an exchange')]
        public int $dismissed,
    ) {
    }
}
