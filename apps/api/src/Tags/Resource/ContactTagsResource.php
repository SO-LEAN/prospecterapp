<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Every tag a contact carries, each list in the order of the names of the tags (ADR 0141): its own, put by hand and
 * taken off; those it inherits from the organizations naming it; the automatic ones of how it is reached.
 */
final readonly class ContactTagsResource
{
    /**
     * @param list<TagResource>          $tags
     * @param list<InheritedTagResource> $inherited
     * @param list<string>               $automatic
     */
    public function __construct(
        #[OA\Property(description: 'Put on the contact by hand')]
        public array $tags,
        #[OA\Property(description: 'Put by hand on organizations naming the contact, its own left out')]
        public array $inherited,
        #[OA\Property(description: 'Set from how the contact is reached: #EMAIL, #PHONE, #SMS for a mobile, #LINKEDIN', items: new OA\Items(type: 'string', example: '#EMAIL'))]
        public array $automatic,
        #[SerializedName('_links')]
        public TagsLinksResource $links,
    ) {
    }
}
