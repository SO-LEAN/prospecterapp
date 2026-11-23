<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;

/**
 * The tags proposed to the member's account (ADR 0138): the predefined tags by family, then the account's own tags. The
 * automatic tags are listed apart, to search the contacts by and never put by hand. They are the tags for how a contact
 * is reached, then the tags for where its organizations are (ADR 0142).
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
        #[OA\Property(description: 'The tags that its organizations and contacts carry and that are not predefined, sorted by name', example: ['#SALON_2026'])]
        public array $own,
        #[OA\Property(description: 'The tags that Prospecter sets, to search the contacts by and never put by hand: the tags for how a contact is reached, then the geographic tags its organizations carry, sorted by name', example: ['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN', '#67', '#GRAND_EST', '#STRASBOURG'])]
        public array $automatic,
    ) {
    }
}
