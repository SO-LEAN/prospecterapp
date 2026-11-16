<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * The exchanges pushed into the calendars of their holders, by their exchange.
 */
interface CalendarEntryRepository
{
    public function find(ExchangeId $exchange): ?CalendarEntry;

    /**
     * Those of the exchanges with the contact.
     *
     * @return list<CalendarEntry>
     */
    public function ofContact(PersonId $contact): array;

    /**
     * Those of the exchanges pushed into the calendar of the member.
     *
     * @return list<CalendarEntry>
     */
    public function ofMember(MemberId $member): array;

    public function save(CalendarEntry $entry): void;

    public function remove(CalendarEntry $entry): void;
}
