<?php

namespace App\Campaigning\Resource;

use OpenApi\Attributes as OA;

/**
 * The message of a campaign rendered for one contact by the core (ADR 0144).
 */
final readonly class CampaignMessageResource
{
    public function __construct(
        #[OA\Property(description: 'The subject of an e-mail')]
        public ?string $subject,
        public string $text,
    ) {
    }
}
