<?php

declare(strict_types=1);

namespace App\Tags\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Every tag a contact carries, each list sorted by name (ADR 0141). Its own tags are put on and taken off by hand. It
 * inherits tags from the organizations that name it. Its automatic tags come from how it is reached.
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
        #[OA\Property(description: 'Put by hand on the organizations that name the contact, without the contact\'s own tags')]
        public array $inherited,
        #[OA\Property(description: 'Set from how the contact is reached: #EMAIL, #PHONE, #SMS for a mobile, #LINKEDIN', items: new OA\Items(type: 'string', example: '#EMAIL'))]
        public array $automatic,
        #[SerializedName('_links')]
        public TagsLinksResource $links,
    ) {
    }
}
