<?php

namespace App\Contacts\Resource;

use App\PersonalData\Resource\PersonContactDetailsResource;
use App\PersonalData\Resource\PersonNameResource;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final readonly class OrganizationContactResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        #[OA\Property(description: 'How one addresses the contact, x when unknown, read in clear like their name', enum: ['ms', 'mr', 'x'])]
        public string $title,
        public ?string $jobTitle,
        public PersonContactDetailsResource $contactDetails,
        public bool $masked,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public OrganizationContactLinksResource $links,
    ) {
    }
}
