<?php

namespace App\Prospecting\Input;

use App\Shared\Input\AddressInput;
use App\Shared\Input\LegalIdentityInput;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspecting;
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
        public OrganizationContactDetailsInput $contactDetails = new OrganizationContactDetailsInput(),
        #[Assert\Valid]
        public ?AddressInput $address = null,
        #[Assert\Length(max: 255)]
        public ?string $observations = null,
        #[Assert\Uuid]
        public ?string $holdingId = null,
    ) {
    }

    /**
     * The prospecting of this organization by a prospector, under this id, the same from the screen and from an AI.
     */
    public function commandOf(string $prospectorId, string $id, \DateTimeImmutable $executedAt): StartProspecting
    {
        $address = $this->address ?? new AddressInput();

        return new StartProspecting(
            prospectorId: $prospectorId,
            id: $id,
            corporateName: $this->legalIdentity->corporateName,
            form: $this->legalIdentity->form,
            type: $this->legalIdentity->type,
            email: $this->contactDetails->email,
            phoneNumber: $this->contactDetails->phoneNumber,
            language: $this->contactDetails->language,
            street: $address->street,
            postalCode: $address->postalCode,
            city: $address->city,
            country: $address->country,
            observations: $this->observations,
            holdingId: $this->holdingId,
            executedAt: $executedAt,
            links: $this->contactDetails->links,
            website: $this->contactDetails->website,
        );
    }
}
