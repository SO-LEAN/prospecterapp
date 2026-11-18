<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Siret;

/**
 * An establishment as the public directory of the companies lists it: its SIRET, the name of its company, its address
 * on one line, its activity (its NAF code), whether it closed, and the INSEE code of its municipality.
 */
final readonly class Establishment
{
    public function __construct(
        public Siret $siret,
        public string $name,
        public string $address,
        public ?string $activity,
        public bool $closed,
        public ?string $municipalityCode,
    ) {
    }
}
