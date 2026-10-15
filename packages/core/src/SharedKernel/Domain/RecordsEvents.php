<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * An aggregate records what happened to it, in the language its context publishes; the use case releases the
 * events once the aggregate is saved, and publishes them (ADR 0042).
 */
trait RecordsEvents
{
    /** @var list<object> */
    private array $recordedEvents = [];

    /**
     * @return list<object> the events recorded since the last release, oldest first
     */
    public function releaseEvents(): array
    {
        $released = $this->recordedEvents;
        $this->recordedEvents = [];

        return $released;
    }

    private function recordThat(object $event): void
    {
        $this->recordedEvents[] = $event;
    }
}
