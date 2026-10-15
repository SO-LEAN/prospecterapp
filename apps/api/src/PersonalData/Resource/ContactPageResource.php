<?php

namespace App\PersonalData\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final readonly class ContactPageResource
{
    /**
     * @param list<ContactResource> $items
     */
    public function __construct(
        public array $items,
        public int $page,
        public int $totalPages,
        public int $totalItems,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ContactPageLinksResource $links,
    ) {
    }
}
