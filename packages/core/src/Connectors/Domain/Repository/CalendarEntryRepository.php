<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Repository;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

/**
 * The exchanges pushed into their holders' calendars, found by their exchange.
 */
interface CalendarEntryRepository
{
    public function find(ExchangeId $exchange): ?CalendarEntry;

    /**
     * The entries of the exchanges with the contact.
     *
     * @return list<CalendarEntry>
     */
    public function ofContact(ContactId $contact): array;

    /**
     * The entries of the exchanges pushed into the member's calendar.
     *
     * @return list<CalendarEntry>
     */
    public function ofMember(MemberId $member): array;

    public function save(CalendarEntry $entry): void;

    public function remove(CalendarEntry $entry): void;
}
