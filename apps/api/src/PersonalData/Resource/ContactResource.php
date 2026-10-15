<?php

namespace App\PersonalData\Resource;

use App\Shared\Resource\ContactDetailsResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * A person the account prospects, in clear for a member with the right to manage personal data, masked for
 * the others (ADR 0031).
 */
final readonly class ContactResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        public ?string $jobTitle,
        public ContactDetailsResource $contactDetails,
        #[OA\Property(description: 'The data are masked: the reader has no right to read personal data in clear')]
        public bool $masked,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public ContactLinksResource $links,
    ) {
    }
}
