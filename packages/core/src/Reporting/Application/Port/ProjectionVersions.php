<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\Port;

/**
 * The figures' versions. The members read the active version. A rebuild writes another version, which the members
 * do not see until it is complete.
 */
interface ProjectionVersions
{
    /**
     * Returns null before the first rebuild.
     */
    public function active(): ?int;

    /**
     * @return list<int> the active version and the one being built: a received fact is recorded in each of them
     */
    public function live(): array;

    /**
     * Stops the version being built, if any, from being live. Received facts are no longer recorded in it.
     */
    public function abandonBuilding(): void;

    /**
     * Removes every version but the active one, with its facts and figures. This removes what an abandoned rebuild
     * left behind, and what a recording wrote in a previous version after its removal.
     */
    public function removeAbandoned(): void;

    /**
     * Makes the version live beside the active one. Received facts are recorded in it too.
     */
    public function startBuilding(int $version): void;

    /**
     * Makes the built version the active one. Removes the previous active version, with its facts and figures.
     */
    public function activate(int $version): void;
}
