<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The timeline of a contact by the moment of its exchanges, and the exchanges kept for an organization, from
 * the items the repository writes next to them (ADR 0074).
 */
class DynamoExchangeRepositoryTest extends ApiTestCase
{
    private const string NOW = '2026-10-18 19:00 UTC';

    private Prospector $prospector;
    private PersonId $contact;
    private OrganizationId $acme;
    private OrganizationId $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), true);
        $this->contact = new PersonId(self::newIdentity());
        $this->acme = new OrganizationId(self::newIdentity());
        $this->group = new OrganizationId(self::newIdentity());
    }

    public function testTheTimelineGoesFromTheMostRecentMomentInSlices(): void
    {
        $exchanges = $this->exchanges();
        $older = $this->noted('2026-10-10 09:00 UTC');
        $recent = $this->noted('2026-10-15 14:30 UTC');
        $oldest = $this->noted('2026-10-01 08:00 UTC');
        array_map($exchanges->save(...), [$older, $recent, $oldest]);

        $first = $exchanges->timelineOf($this->contact, $this->prospector->accountId, null, 2);
        $following = $exchanges->timelineOf($this->contact, $this->prospector->accountId, $first->next, 2);

        $this->assertEquals([$recent->id(), $older->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $first->content));
        $this->assertEquals([$oldest->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $following->content));
        $this->assertNull($following->next);
    }

    public function testAnotherAccountHasItsOwnTimeline(): void
    {
        $this->exchanges()->save($this->noted('2026-10-10 09:00 UTC'));

        $this->assertSame([], $this->exchanges()->timelineOf($this->contact, new AccountId(self::newIdentity()), null, 20)->content);
    }

    public function testAnExchangeIsFoundByTheOrganizationsItIsKeptFor(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchange->stopKeepingFor($this->acme, by: $this->prospector, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $this->assertSame([], $exchanges->keptFor($this->acme));
        $this->assertEquals([$exchange->id()], array_map(static fn (Exchange $kept): ExchangeId => $kept->id(), $exchanges->keptFor($this->group)));
        $this->assertEquals([$this->group], $exchanges->keptFor($this->group)[0]->keptFor());
    }

    public function testARemovedExchangeIsFoundNowhere(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchanges->remove($exchange);

        $this->assertSame([], $exchanges->keptFor($this->acme));
        $this->assertSame([], $exchanges->keptFor($this->group));
        $this->assertSame([], $exchanges->timelineOf($this->contact, $this->prospector->accountId, null, 20)->content);
    }

    private function noted(string $occurredAt): Exchange
    {
        return Exchange::notedBy($this->prospector, new ExchangeId(self::newIdentity()), $this->contact, [$this->acme, $this->group], new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt), 'Rappeler lundi'), new \DateTimeImmutable(self::NOW));
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
