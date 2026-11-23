<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;

/**
 * The figures' versions over the InMemoryDatabase. A scenario may run a gesture at three moments:
 * - while a rebuild removes the abandoned versions, between their read and their removal, as DynamoDB scans then
 *   deletes;
 * - just before the next activation, once the rebuild has read every fact again, as a member may while a rebuild runs;
 * - just after the next activation, as the worker may write in the previous version a recording that read it live.
 *
 * A scenario may also interrupt the next activation. The version then stays in the building state.
 */
final class InMemoryProjectionVersions implements ProjectionVersions
{
    /** @var (\Closure(): void)|null */
    private ?\Closure $whileRemovingTheAbandoned = null;
    /** @var (\Closure(): void)|null */
    private ?\Closure $beforeTheNextActivation = null;
    /** @var (\Closure(): void)|null */
    private ?\Closure $afterTheNextActivation = null;
    private bool $interruptTheNextActivation = false;

    public function __construct(private readonly InMemoryDatabase $database)
    {
    }

    /**
     * @param \Closure(): void $gesture
     */
    public function whileRemovingTheAbandoned(\Closure $gesture): void
    {
        $this->whileRemovingTheAbandoned = $gesture;
    }

    /**
     * @param \Closure(): void $gesture
     */
    public function beforeTheNextActivation(\Closure $gesture): void
    {
        $this->beforeTheNextActivation = $gesture;
    }

    /**
     * @param \Closure(): void $gesture
     */
    public function afterTheNextActivation(\Closure $gesture): void
    {
        $this->afterTheNextActivation = $gesture;
    }

    public function interruptTheNextActivation(): void
    {
        $this->interruptTheNextActivation = true;
    }

    /**
     * @return list<int>
     */
    public function versionsKeepingFactsOrFigures(): array
    {
        return $this->database->versionsKept();
    }

    public function active(): ?int
    {
        return $this->database->activeVersion();
    }

    public function live(): array
    {
        return array_values(array_filter([$this->database->activeVersion(), $this->database->versionBuilding()], static fn (?int $version): bool => null !== $version));
    }

    public function abandonBuilding(): void
    {
        $this->database->abandonBuilding();
    }

    public function removeAbandoned(): void
    {
        $read = $this->database->itemsOfEveryVersionBut($this->active());
        [$gesture, $this->whileRemovingTheAbandoned] = [$this->whileRemovingTheAbandoned, null];
        self::run($gesture);
        $this->database->removeItems($read);
    }

    public function startBuilding(int $version): void
    {
        $this->database->startBuilding($version);
    }

    public function activate(int $version): void
    {
        if ($this->interruptTheNextActivation) {
            $this->interruptTheNextActivation = false;

            throw new RebuildInterrupted(sprintf('The activation of version %d was cancelled', $version));
        }

        [$before, $this->beforeTheNextActivation] = [$this->beforeTheNextActivation, null];
        [$after, $this->afterTheNextActivation] = [$this->afterTheNextActivation, null];
        self::run($before);
        $this->database->activate($version);
        self::run($after);
    }

    /**
     * @param (\Closure(): void)|null $gesture
     */
    private static function run(?\Closure $gesture): void
    {
        if (null !== $gesture) {
            $gesture();
        }
    }
}
