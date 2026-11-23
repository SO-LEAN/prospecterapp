<?php

declare(strict_types=1);

namespace App\Campaigning\Resource;

use OpenApi\Attributes as OA;

/** A campaign's message, which the core renders for one contact (ADR 0144). */
final readonly class CampaignMessageResource
{
    public function __construct(
        #[OA\Property(description: 'An e-mail\'s subject')]
        public ?string $subject,
        public string $text,
    ) {
    }
}
