<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;

final readonly class TagFamilyResource
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        #[OA\Property(enum: ['organizationType', 'relationship', 'technology'])]
        public string $family,
        #[OA\Property(description: 'In the order proposed', example: ['#APPORTEUR', '#ESN'])]
        public array $tags,
    ) {
    }
}
