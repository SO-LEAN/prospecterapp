<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;

final readonly class InMemoryCalendarEntryRepository implements CalendarEntryRepository
{
    public function __construct(private InMemoryDatabase $database)
    {
    }

    public function find(ExchangeId $exchange): ?CalendarEntry
    {
        return $this->database->calendarEntry($exchange->value);
    }

    public function save(CalendarEntry $entry): void
    {
        $this->database->storeCalendarEntry($entry);
    }

    public function remove(CalendarEntry $entry): void
    {
        $this->database->forgetCalendarEntry($entry);
    }
}
