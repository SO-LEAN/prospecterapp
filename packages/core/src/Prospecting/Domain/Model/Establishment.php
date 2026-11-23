<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Company\Siret;

/**
 * An establishment as the public company directory lists it: its SIRET, its company's name, its address on one line,
 * its activity (its NAF code), whether it closed, and its municipality's INSEE code.
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
