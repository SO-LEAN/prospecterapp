<?php

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;

final readonly class CivilityInput
{
    public function __construct(
        #[OA\Property(description: 'How one addresses the contact, null to no longer note it', enum: ['ms', 'mr'], nullable: true)]
        public ?string $civility = null,
    ) {
    }
}
