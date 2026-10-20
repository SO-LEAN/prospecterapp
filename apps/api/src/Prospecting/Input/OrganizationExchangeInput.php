<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An exchange noted from the card of an organization, shaped like `OrganizationExchangeResource`: with the
 * organization itself, or with the contact of it `personId` names.
 */
final readonly class OrganizationExchangeInput
{
    public function __construct(
        #[Assert\Valid]
        public ExchangeInput $exchange = new ExchangeInput(),
        #[Assert\Uuid]
        #[OA\Property(description: 'The contact of the organization it was with; left out, it was with the organization itself')]
        public ?string $personId = null,
    ) {
    }
}
