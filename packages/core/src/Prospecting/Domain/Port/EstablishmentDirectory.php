<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Port;

use Prospecter\Core\Prospecting\Domain\Model\Establishment;

/**
 * The public company directory. It finds companies' establishments by a name that resembles theirs, as the directory
 * judges it. The closest come first.
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
     * The establishments in a municipality, given by its INSEE code.
     *
     * @return list<Establishment>
     *
     * @throws \RuntimeException when the directory does not answer
     */
    public function namedIn(string $name, string $municipalityCode): array;
}
