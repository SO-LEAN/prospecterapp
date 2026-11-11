<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\Port;

/**
 * The versions of the figures: the active one, read by the members, and the one a rebuild is writing, out of their
 * sight until it is complete.
 */
interface ProjectionVersions
{
    /**
     * Null before the first rebuild.
     */
    public function active(): ?int;

    /**
     * @return list<int> the active version and the one being built, those a fact received is recorded in
     */
    public function live(): array;

    public function startBuilding(int $version): void;

    /**
     * The version built becomes the active one; the former active one is removed, with its facts and its figures.
     */
    public function activate(int $version): void;
}
