<?php

namespace App\Api\Resource;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Each part is changed by its own intention: PATCH name, user-name, contact-details, organization/legal-identity.
 */
final readonly class AccountResource
{
    public function __construct(
        public string $userName,
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
