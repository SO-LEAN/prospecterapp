<?php

declare(strict_types=1);

namespace App\PersonalData\Input;

use OpenApi\Attributes as OA;

final readonly class TitleInput
{
    public function __construct(
        #[OA\Property(description: 'How one addresses the contact, x when unknown', enum: ['ms', 'mr', 'x'])]
        public string $title = 'x',
    ) {
    }
}
