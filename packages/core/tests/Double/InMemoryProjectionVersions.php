<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;

/**
 * The versions of the figures over the InMemoryDatabase. A scenario may do a gesture just before the next activation,
 * once the rebuild read every fact again, as a member may while a rebuild runs.
 */
final class InMemoryProjectionVersions implements ProjectionVersions
{
    /** @var (\Closure(): void)|null */
    private ?\Closure $beforeTheNextActivation = null;

    public function __construct(private readonly InMemoryDatabase $database)
    {
    }

    /**
     * @param \Closure(): void $gesture
     */
    public function beforeTheNextActivation(\Closure $gesture): void
    {
        $this->beforeTheNextActivation = $gesture;
    }

    public function active(): ?int
    {
        return $this->database->activeVersion();
    }

    public function live(): array
    {
        return array_values(array_filter([$this->database->activeVersion(), $this->database->versionBuilding()], static fn (?int $version): bool => null !== $version));
    }

    public function startBuilding(int $version): void
    {
        $this->database->startBuilding($version);
    }

    public function activate(int $version): void
    {
        [$gesture, $this->beforeTheNextActivation] = [$this->beforeTheNextActivation, null];

        if (null !== $gesture) {
            $gesture();
        }

        $this->database->activate($version);
    }
}
