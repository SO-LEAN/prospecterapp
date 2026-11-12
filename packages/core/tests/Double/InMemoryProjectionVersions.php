<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;

/**
 * The versions of the figures over the InMemoryDatabase. A scenario may do a gesture while a rebuild removes the
 * abandoned versions, between their reading and their removal, as DynamoDB scans then deletes, or just before the next
 * activation, once the rebuild read every fact again, as a member may while a rebuild runs, or just after it, as the
 * worker may write in the former version a recording read live before; it may interrupt the next activation, the
 * version left being built.
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
