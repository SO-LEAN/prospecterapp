<?php

declare(strict_types=1);

namespace App\Prospecting\Input;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrganizationPlannedExchangeInput
{
    public function __construct(
        #[Assert\Valid]
        public PlannedExchangeInput $exchange = new PlannedExchangeInput(),
        #[Assert\Uuid]
        #[OA\Property(description: 'The organization\'s contact it is planned with. When left out, it is planned with the organization itself')]
        public ?string $personId = null,
    ) {
    }
}
