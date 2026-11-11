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

    /**
     * The version being built, if any, is no longer live: the facts received are no longer recorded in it.
     */
    public function abandonBuilding(): void;

    /**
     * Every version but the active one is removed, its facts and its figures: what an abandoned rebuild left, and
     * what a recording wrote in a former version after its removal.
     */
    public function removeAbandoned(): void;

    /**
     * The version becomes live beside the active one: the facts received are recorded in it too.
     */
    public function startBuilding(int $version): void;

    /**
     * The version built becomes the active one; the former active one is removed, with its facts and its figures.
     */
    public function activate(int $version): void;
}
