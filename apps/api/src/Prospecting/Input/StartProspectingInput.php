<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use App\Shared\Input\AddressInput;
use App\Shared\Input\LegalIdentityInput;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspecting;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * What is known about an organization when its prospecting starts, shaped like OrganizationResource.
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
     * A prospector starts prospecting this organization under this id. The command is the same from the screen and from
     * an AI.
     */
    public function commandOf(string $prospectorId, string $id, \DateTimeImmutable $executedAt): StartProspecting
    {
        return $this->command($prospectorId, $id, $executedAt, byAnAI: false);
    }

    /**
     * The same command, by an AI acting as the prospector.
     */
    public function commandOfAnAI(string $prospectorId, string $id, \DateTimeImmutable $executedAt): StartProspecting
    {
        return $this->command($prospectorId, $id, $executedAt, byAnAI: true);
    }

    private function command(string $prospectorId, string $id, \DateTimeImmutable $executedAt, bool $byAnAI): StartProspecting
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
            byAnAI: $byAnAI,
        );
    }
}
