<?php

namespace App\Tags\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The tags put by hand on an organization or a contact, in the order of their names (ADR 0138).
 */
final readonly class TagsResource
{
    /**
     * @param list<TagResource> $tags
     */
    public function __construct(
        public array $tags,
        #[SerializedName('_links')]
        public TagsLinksResource $links,
    ) {
    }
}
