<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchEstablishments;

/**
 * An establishment proposed for an organization: its SIRET, the name of its company, its address on one line, its
 * activity (its NAF code), and whether it closed.
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
