<?php

namespace App\Contacts\Resource;

use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A slice of the contacts carrying every tag chosen, and how many carry them in all.
 */
final readonly class TaggedContactListResource
{
    /**
     * @param list<ContactInListResource> $items
     */
    public function __construct(
        public array $items,
        #[OA\Property(description: 'How many contacts carry every tag chosen, in all the slices', example: 12)]
        public int $found,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public TaggedContactListLinksResource $links,
    ) {
    }
}
