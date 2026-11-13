<?php

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrganizationPlannedExchangeInput
{
    public function __construct(
        #[Assert\Valid]
        public PlannedExchangeInput $exchange = new PlannedExchangeInput(),
        #[Assert\Uuid]
        #[OA\Property(description: 'The contact of the organization it is planned with; left out, with the organization itself')]
        public ?string $personId = null,
    ) {
    }
}
