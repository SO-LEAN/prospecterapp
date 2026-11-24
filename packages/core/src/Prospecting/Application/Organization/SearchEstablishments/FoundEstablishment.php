<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Organization\SearchEstablishments;

/**
 * An establishment proposed for an organization: its SIRET, its company's name, its address on one line, its
 * activity (its NAF code), and whether it has closed.
 */
final readonly class FoundEstablishment
{
    public function __construct(
        public string $siret,
        public string $name,
        public string $address,
        public ?string $activity,
        public bool $closed,
    ) {
    }
}
