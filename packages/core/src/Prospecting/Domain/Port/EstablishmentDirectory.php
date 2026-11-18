<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\Establishment;

/**
 * The public directory of the companies: their establishments, found by a name that resembles theirs, as the directory
 * judges it, the closest first.
 */
interface EstablishmentDirectory
{
    /**
     * @return list<Establishment>
     *
     * @throws \RuntimeException when the directory does not answer
     */
    public function named(string $name): array;

    /**
     * Those of a municipality, by its INSEE code.
     *
     * @return list<Establishment>
     *
     * @throws \RuntimeException when the directory does not answer
     */
    public function namedIn(string $name, string $municipalityCode): array;
}
