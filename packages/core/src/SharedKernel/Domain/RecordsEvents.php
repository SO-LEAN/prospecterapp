<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

use Prospecter\Core\SharedKernel\Domain\Event\DomainEvent;

/**
 * The events of an aggregate (ReleasesEvents): its intention methods record them, the use case releases them
 * once the aggregate is saved, and publishes them (ADR 0042, 0043).
 */
trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    /**
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $released = $this->recordedEvents;
        $this->recordedEvents = [];

        return $released;
    }

    private function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
