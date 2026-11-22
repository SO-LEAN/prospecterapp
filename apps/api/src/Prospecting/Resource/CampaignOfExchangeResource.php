<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use OpenApi\Attributes as OA;

/**
 * The campaign an exchange was noted in, named as Campaigns names it now (ADR 0145).
 */
final readonly class CampaignOfExchangeResource
{
    public function __construct(
        public string $id,
        #[OA\Property(description: 'Its name, none once the campaign was erased')]
        public ?string $name,
    ) {
    }
}
