<?php

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;

final readonly class CivilityInput
{
    public function __construct(
        #[OA\Property(description: 'How one addresses the contact, x when unknown', enum: ['ms', 'mr', 'x'])]
        public string $civility = 'x',
    ) {
    }
}
