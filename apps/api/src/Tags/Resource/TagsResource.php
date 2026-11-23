<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * An organization's tags: the tags put by hand, sorted by name (ADR 0138), and the automatic tags for where it is (ADR
 * 0142).
 */
final readonly class TagsResource
{
    /**
     * @param list<TagResource> $tags
     * @param list<string>      $automatic
     */
    public function __construct(
        public array $tags,
        #[OA\Property(description: 'Set from where the organization is: the surrounding big city, its department and its region, in that order', items: new OA\Items(type: 'string', example: '#STRASBOURG'))]
        public array $automatic,
        #[SerializedName('_links')]
        public TagsLinksResource $links,
    ) {
    }
}
