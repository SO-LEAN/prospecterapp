<?php

declare(strict_types=1);

namespace Tests\App\Connectors;

use Prospecter\Core\Connectors\Domain\Model\CalendarEntry;
use Prospecter\Core\Connectors\Domain\Model\Channel;
use Prospecter\Core\Connectors\Domain\Model\ContactId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeId;
use Prospecter\Core\Connectors\Domain\Model\ExchangeStatus;
use Prospecter\Core\Connectors\Domain\Model\HeldExchange;
use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\Connectors\Domain\Model\Provider;
use Prospecter\Core\Connectors\Domain\Repository\CalendarEntryRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges pushed, in the table of Connectors (ADR 0127): found by their exchange, those with a contact by the
 * contact, those of a member by the member, forgotten with them; where their push stands kept (ADR 0133).
 */
class DynamoCalendarEntryRepositoryTest extends ApiTestCase
{
    public function testAnEntryIsFoundByItsExchangeAndByItsContactUntilRemoved(): void
    {
        $contact = new ContactId(self::newIdentity());
        $withTheContact = self::entryOf(self::heldExchange($contact));
        $withTheOrganization = self::entryOf(self::heldExchange(null));

        $this->entries()->save($withTheContact);
        $this->entries()->save($withTheOrganization);

        $this->assertEquals($withTheContact, $this->entries()->find($withTheContact->exchange()));
        $this->assertEquals($withTheOrganization, $this->entries()->find($withTheOrganization->exchange()));
        $this->assertEquals([$withTheContact], $this->entries()->ofContact($contact));
        $this->assertEquals([$withTheContact], $this->entries()->ofMember($withTheContact->member()));

        $this->entries()->remove($withTheContact);

        $this->assertNull($this->entries()->find($withTheContact->exchange()));
        $this->assertSame([], $this->entries()->ofContact($contact));
        $this->assertSame([], $this->entries()->ofMember($withTheContact->member()));
    }

    public function testWhereItsPushStandsIsKept(): void
    {
        $exchange = self::heldExchange(null);
        $entry = self::entryOf($exchange);
        $entry->pushed($exchange, new \DateTimeImmutable('2026-10-15 09:00 UTC'));
        $this->entries()->save($entry);
        $this->assertEquals($entry, $this->entries()->find($entry->exchange()));

        $entry->refused('calendarRefusedThePush', new \DateTimeImmutable('2026-10-15 10:00 UTC'));
        $this->entries()->save($entry);

        $this->assertEquals($entry, $this->entries()->find($entry->exchange()));
        $this->assertTrue($this->entries()->find($entry->exchange())?->hasFailed());
    }

    public function testThatItsEventInvitesAGuestIsKept(): void
    {
        $exchange = new HeldExchange(new ExchangeId(self::newIdentity()), new AccountId(self::newIdentity()), new MemberId(self::newIdentity()), ExchangeStatus::Planned, Channel::VideoCall, new \DateTimeImmutable('2026-10-20 14:00 UTC'), 15, null, new ContactId(self::newIdentity()), null, null, linkRequested: true, bookedByLink: true);
        $entry = self::entryOf($exchange);
        $entry->pushed($exchange, new \DateTimeImmutable('2026-10-15 09:00 UTC'));

        $this->entries()->save($entry);

        $this->assertTrue($this->entries()->find($entry->exchange())?->invitesAGuestStillToComeAt(new \DateTimeImmutable('2026-10-15 10:00 UTC')));
    }

    private static function entryOf(HeldExchange $exchange): CalendarEntry
    {
        return CalendarEntry::of($exchange, Provider::Google, new \DateTimeImmutable('2026-10-15 09:00 UTC'));
    }

    private static function heldExchange(?ContactId $contact): HeldExchange
    {
        return new HeldExchange(new ExchangeId(self::newIdentity()), new AccountId(self::newIdentity()), new MemberId(self::newIdentity()), ExchangeStatus::Planned, Channel::VideoCall, new \DateTimeImmutable('2026-10-20 14:00 UTC'), 30, null, $contact, new OrganizationId(self::newIdentity()), 'ACME');
    }

    private function entries(): CalendarEntryRepository
    {
        return self::service(CalendarEntryRepository::class);
    }
}
