<?php

namespace App\Api\Resource;

use Symfony\Component\Serializer\Attribute\SerializedName;

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
        public int $activeOrganizations,
        #[SerializedName('_links')]
        public OrganizationLinksResource $links,
    ) {
    }
}
