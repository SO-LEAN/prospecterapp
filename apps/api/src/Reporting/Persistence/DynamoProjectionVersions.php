<?php

namespace App\Reporting\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Table;
use Prospecter\Core\Reporting\Application\Port\ProjectionVersions;
use Psr\Clock\ClockInterface;

/**
 * The versions of the figures in the table of Reporting: PK `PROJECTION`, SK `ACTIVE` (`version`) and `BUILDING`
 * (`version`, `since` the moment the rebuild started it). The version being built is abandoned by removing `BUILDING`,
 * written in its unit; the abandoned versions are every version but the active one, removed by a scan then deletions
 * written at once: what an interrupted rebuild left, and what a recording wrote in the former version after its removal.
 * Once activated, the former version is removed the same way, its facts and its figures, a projection that can be
 * rebuilt (ADR 0103, 0106).
 */
final readonly class DynamoProjectionVersions implements ProjectionVersions
{
    public function __construct(private Table $reportingTable, private ClockInterface $clock)
    {
    }

    public function active(): ?int
    {
        return $this->versionAt(ReportingKeys::ACTIVE);
    }

    public function live(): array
    {
        $items = $this->reportingTable->items([[ReportingKeys::PROJECTION, ReportingKeys::ACTIVE], [ReportingKeys::PROJECTION, ReportingKeys::BUILDING]]);
        $versions = array_values(array_unique(array_map(static fn (array $item): int => (int) $item['version'], $items)));
        sort($versions);

        return $versions;
    }

    public function abandonBuilding(): void
    {
        $this->reportingTable->transact([$this->reportingTable->deleting(ReportingKeys::PROJECTION, ReportingKeys::BUILDING)]);
    }

    public function removeAbandoned(): void
    {
        $this->removeEveryVersionBut($this->active());
    }

    public function startBuilding(int $version): void
    {
        $this->reportingTable->transact([$this->reportingTable->putting(['PK' => ReportingKeys::PROJECTION, 'SK' => ReportingKeys::BUILDING, 'version' => $version, 'since' => Documents::moment($this->clock->now())])]);
    }

    public function activate(int $version): void
    {
        $former = $this->active();
        $this->reportingTable->batch([['PK' => ReportingKeys::PROJECTION, 'SK' => ReportingKeys::ACTIVE, 'version' => $version]], [[ReportingKeys::PROJECTION, ReportingKeys::BUILDING]]);

        if (null !== $former && $former !== $version) {
            $this->remove($former);
        }
    }

    private function versionAt(string $sortKey): ?int
    {
        $item = $this->reportingTable->item(ReportingKeys::PROJECTION, $sortKey);

        return null === $item ? null : (int) $item['version'];
    }

    private function removeEveryVersionBut(?int $kept): void
    {
        foreach (ReportingKeys::partitionsOfEveryVersion() as $beginning) {
            $removed = array_filter($this->reportingTable->scan($beginning), static fn (array $item): bool => ReportingKeys::versionOf($item['PK']) !== $kept);
            $this->reportingTable->batch([], array_values(array_map(static fn (array $item): array => [$item['PK'], $item['SK']], $removed)));
        }
    }

    /**
     * Its facts and its figures.
     */
    private function remove(int $version): void
    {
        foreach (ReportingKeys::partitionsOf($version) as $beginning) {
            $this->reportingTable->batch([], array_map(static fn (array $item): array => [$item['PK'], $item['SK']], $this->reportingTable->scan($beginning)));
        }
    }
}
