<?php

namespace Tests\App\Connectors;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeStatus;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges pushed, in the table of Connectors (ADR 0127): found by their exchange, and those with a contact by
 * the contact, forgotten with them.
 */
class DynamoCalendarEntryRepositoryTest extends ApiTestCase
{
    public function testAnEntryIsFoundByItsExchangeAndByItsContactUntilRemoved(): void
    {
        $contact = new PersonId(self::newIdentity());
        $withTheContact = self::entryOf(self::heldExchange($contact));
        $withTheOrganization = self::entryOf(self::heldExchange(null));

        $this->entries()->save($withTheContact);
        $this->entries()->save($withTheOrganization);

        $this->assertEquals($withTheContact, $this->entries()->find($withTheContact->exchange()));
        $this->assertEquals($withTheOrganization, $this->entries()->find($withTheOrganization->exchange()));
        $this->assertEquals([$withTheContact], $this->entries()->ofContact($contact));

        $this->entries()->remove($withTheContact);

        $this->assertNull($this->entries()->find($withTheContact->exchange()));
        $this->assertSame([], $this->entries()->ofContact($contact));
    }

    private static function entryOf(HeldExchange $exchange): CalendarEntry
    {
        return CalendarEntry::of($exchange, Provider::Google);
    }

    private static function heldExchange(?PersonId $contact): HeldExchange
    {
        return new HeldExchange(new ExchangeId(self::newIdentity()), new AccountId(self::newIdentity()), new MemberId(self::newIdentity()), ExchangeStatus::Planned, Channel::VideoCall, new \DateTimeImmutable('2026-10-20 14:00 UTC'), 30, null, $contact, new OrganizationId(self::newIdentity()), 'ACME');
    }

    private function entries(): CalendarEntryRepository
    {
        return self::getContainer()->get(CalendarEntryRepository::class);
    }
}
