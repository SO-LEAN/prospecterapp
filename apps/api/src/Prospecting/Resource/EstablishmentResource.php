<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use OpenApi\Attributes as OA;

/**
 * An establishment from the company directory that an organization may be.
 */
final readonly class EstablishmentResource
{
    public function __construct(
        #[OA\Property(example: '55210055400013')]
        public string $siret,
        #[OA\Property(description: 'Its company\'s name', example: 'ACME')]
        public string $name,
        #[OA\Property(description: 'Its address on one line', example: '17 RUE DOCTEUR BOUCHUT 69003 LYON')]
        public string $address,
        #[OA\Property(description: 'Its NAF code', example: '62.02A')]
        public ?string $activity,
        public bool $closed,
    ) {
    }
}
