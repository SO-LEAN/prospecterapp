<?php

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;

/**
 * The tags proposed to the account of the member (ADR 0138): the predefined ones by family, then its own.
 */
final readonly class SuggestedTagsResource
{
    /**
     * @param list<TagFamilyResource> $families
     * @param list<string>            $own
     */
    public function __construct(
        public array $families,
        #[OA\Property(description: 'The tags its organizations and contacts carry that are not predefined, in the order of their names', example: ['#SALON_2026'])]
        public array $own,
    ) {
    }
}
