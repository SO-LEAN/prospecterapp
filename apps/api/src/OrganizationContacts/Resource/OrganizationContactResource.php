<?php

namespace App\OrganizationContacts\Resource;

use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Resource\ContactDetailsResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

final readonly class OrganizationContactResource
{
    public function __construct(
        public string $id,
        public PersonNameResource $name,
        public ?string $jobTitle,
        public ContactDetailsResource $contactDetails,
        public bool $masked,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public OrganizationContactLinksResource $links,
    ) {
    }
}
