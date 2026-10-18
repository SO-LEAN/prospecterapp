<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

final readonly class GeoPoint
{
    public function __construct(
        public float $longitude,
        public float $latitude,
    ) {
    }
}
