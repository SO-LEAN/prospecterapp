<?php

namespace App\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * What is known of an organization when its prospecting starts (the shape of OrganizationResource).
 */
final readonly class StartProspectingInput
{
    public function __construct(
        #[Assert\Valid]
        public LegalIdentityInput $legalIdentity = new LegalIdentityInput(),
        #[Assert\Valid]
        public ContactDetailsInput $contactDetails = new ContactDetailsInput(),
        #[Assert\Valid]
        public ?AddressInput $address = null,
        #[Assert\Length(max: 255)]
        public ?string $observations = null,
        #[Assert\Uuid]
        public ?string $holdingId = null,
    ) {
    }
}
