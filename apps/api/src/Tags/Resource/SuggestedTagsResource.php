<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;

/**
 * The tags proposed to the account of the member (ADR 0138): the predefined ones by family, then its own, and apart
 * the automatic ones, to search the contacts by, never put by hand: those of how a contact is reached, then those of
 * where its organizations are (ADR 0142).
 */
final readonly class SuggestedTagsResource
{
    /**
     * @param list<TagFamilyResource> $families
     * @param list<string>            $own
     * @param list<string>            $automatic
     */
    public function __construct(
        public array $families,
        #[OA\Property(description: 'The tags its organizations and contacts carry that are not predefined, in the order of their names', example: ['#SALON_2026'])]
        public array $own,
        #[OA\Property(description: 'The tags set by Prospecter, to search the contacts by, never put by hand: those of how a contact is reached, then the geographic tags its organizations carry, in the order of their names', example: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN', '#67', '#GRAND_EST', '#STRASBOURG'])]
        public array $automatic,
    ) {
    }
}
