<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use OpenApi\Attributes as OA;

/**
 * The campaign an exchange was logged in, with the name Campaigns currently gives it (ADR 0145).
 */
final readonly class CampaignOfExchangeResource
{
    public function __construct(
        public string $id,
        #[OA\Property(description: 'Its name, or null once the campaign is erased')]
        public ?string $name,
    ) {
    }
}
