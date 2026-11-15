<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;

/**
 * The exchanges pushed into the calendars of their holders, by their exchange.
 */
interface CalendarEntryRepository
{
    public function find(ExchangeId $exchange): ?CalendarEntry;

    public function save(CalendarEntry $entry): void;

    public function remove(CalendarEntry $entry): void;
}
