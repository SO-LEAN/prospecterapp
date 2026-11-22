<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The tags of an organization: those put by hand, in the order of their names (ADR 0138), and the automatic ones of
 * where it is (ADR 0142).
 */
final readonly class TagsResource
{
    /**
     * @param list<TagResource> $tags
     * @param list<string>      $automatic
     */
    public function __construct(
        public array $tags,
        #[OA\Property(description: 'Set from where the organization is: the big city around, its department and its region, the city first', items: new OA\Items(type: 'string', example: '#STRASBOURG'))]
        public array $automatic,
        #[SerializedName('_links')]
        public TagsLinksResource $links,
    ) {
    }
}
