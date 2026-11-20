<?php

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonNameResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A contact of the same organization the contact resembles, by their names: perhaps the same person.
 */
final readonly class PossibleDuplicateOfContactResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public PossibleDuplicateOfContactLinksResource $links,
    ) {
    }
}
