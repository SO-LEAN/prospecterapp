<?php

namespace App\Me\Resource;

use App\PersonalData\Resource\PersonNameResource;
use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\ImageResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Each part is changed by its own intention: PATCH name, contact-details, organization/legal-identity.
 */
final readonly class AccountResource
{
    public function __construct(
        public PersonNameResource $name,
        public ContactDetailsResource $contactDetails,
        public ?ImageResource $picture,
        public AccountOrganizationResource $organization,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public AccountLinksResource $links,
    ) {
    }
}
