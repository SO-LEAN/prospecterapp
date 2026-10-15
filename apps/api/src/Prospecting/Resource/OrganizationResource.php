<?php

namespace App\Prospecting\Resource;

use App\Shared\Resource\ContactDetailsResource;
use App\Shared\Resource\ImageResource;
use App\Shared\Resource\LegalIdentityResource;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * Each part is changed by its own intention: PATCH legal-identity, contact-details, address, observations, holding.
 */
final readonly class OrganizationResource
{
    public function __construct(
        public string $id,
        public string $name,
        public LegalIdentityResource $legalIdentity,
        public ContactDetailsResource $contactDetails,
        public ?AddressResource $address,
        public ?LocationResource $location,
        public ?string $observations,
        public ?ImageResource $logo,
        public ?string $holdingId,
        #[SerializedName('_links')]
        #[Context([AbstractObjectNormalizer::SKIP_NULL_VALUES => true])]
        public OrganizationLinksResource $links,
    ) {
    }
}
