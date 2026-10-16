<?php

namespace App\Contacts\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final readonly class ContactListResource
{
    /**
     * @param list<ContactInListResource> $items
     */
    public function __construct(
        public array $items,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ContactListLinksResource $links,
    ) {
    }
}
