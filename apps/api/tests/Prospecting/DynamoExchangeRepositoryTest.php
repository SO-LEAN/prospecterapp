<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Correction;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The timeline of a contact by the moment of its exchanges, and the exchanges kept for an organization, its
 * timeline, from the items the repository writes next to them (ADR 0074, 0076).
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

        $exchange->stopKeepingFor($this->acme, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
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

    public function testTheTimelineOfAnOrganizationGoesFromTheMostRecentMomentInSlices(): void
    {
        $exchanges = $this->exchanges();
        $withTheContact = $this->noted('2026-10-10 09:00 UTC');
        $withTheOrganization = $this->notedWithTheOrganization('2026-10-15 14:30 UTC');
        $oldest = $this->noted('2026-10-01 08:00 UTC');
        array_map($exchanges->save(...), [$withTheContact, $withTheOrganization, $oldest]);

        $first = $exchanges->timelineOfOrganization($this->acme, null, 2);
        $following = $exchanges->timelineOfOrganization($this->acme, $first->next, 2);

        $this->assertEquals([$withTheOrganization->id(), $withTheContact->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $first->content));
        $this->assertEquals([$oldest->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $following->content));
        $this->assertNull($following->next);
    }

    public function testAnExchangeWithTheOrganizationItselfIsInTheTimelineOfNoContact(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->notedWithTheOrganization('2026-10-15 14:30 UTC');

        $exchanges->save($exchange);

        $this->assertNull($exchanges->timelineOfOrganization($this->acme, null, 20)->content[0]->contact());
        $this->assertSame([], $exchanges->timelineOfOrganization($this->group, null, 20)->content);
    }

    public function testAnExchangeNoLongerKeptForAnOrganizationLeavesItsTimeline(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchange->stopKeepingFor($this->acme, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $this->assertSame([], $exchanges->timelineOfOrganization($this->acme, null, 20)->content);
        $this->assertCount(1, $exchanges->timelineOfOrganization($this->group, null, 20)->content);
    }

    private function notedWithTheOrganization(string $occurredAt): Exchange
    {
        $organization = Organization::prospectedBy($this->prospector, $this->acme, new LegalIdentity('ACME'), new ContactDetails(), new \DateTimeImmutable(self::NOW));

        return Exchange::notedWithTheOrganization($organization, $this->prospector->id, new ExchangeId(self::newIdentity()), new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt)), new \DateTimeImmutable(self::NOW));
    }

    public function testACorrectedExchangeIsReadWithItsCorrection(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);
        $corrected = new ExchangeFacts(Channel::Email, Direction::Outbound, new \DateTimeImmutable('2026-10-12 10:00 UTC'), 'Objet : mission');

        $exchange->correct($corrected, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $read = $exchanges->get($exchange->id());
        $this->assertEquals($corrected, $read->facts());
        $this->assertEquals(new Correction($this->prospector->id, new \DateTimeImmutable(self::NOW)), $read->lastCorrection());
    }

    public function testACorrectedMomentMovesTheExchangeInTheTimeline(): void
    {
        $exchanges = $this->exchanges();
        $first = $this->noted('2026-10-10 09:00 UTC');
        $second = $this->noted('2026-10-11 09:00 UTC');
        array_map($exchanges->save(...), [$first, $second]);

        $first->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-12 09:00 UTC')), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($first);

        $timeline = $exchanges->timelineOf($this->contact, $this->prospector->accountId, null, 20)->content;
        $this->assertEquals([$first->id(), $second->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $timeline));
    }

    public function testACorrectedMomentMovesTheExchangeInTheTimelinesOfItsOrganizations(): void
    {
        $exchanges = $this->exchanges();
        $first = $this->noted('2026-10-10 09:00 UTC');
        $second = $this->notedWithTheOrganization('2026-10-11 09:00 UTC');
        array_map($exchanges->save(...), [$first, $second]);

        $first->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-12 09:00 UTC')), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($first);

        $timeline = $exchanges->timelineOfOrganization($this->acme, null, 20)->content;
        $this->assertEquals([$first->id(), $second->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $timeline));
    }

    public function testTheExchangesWithAContactAreFoundAll(): void
    {
        $exchanges = $this->exchanges();
        $first = $this->noted('2026-10-10 09:00 UTC');
        $second = $this->noted('2026-10-11 09:00 UTC');
        array_map($exchanges->save(...), [$first, $second, $this->notedWithTheOrganization('2026-10-12 09:00 UTC')]);

        $found = $exchanges->withContact($this->contact, $this->prospector->accountId);

        $this->assertEquals([$second->id(), $first->id()], array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $found));
    }

    public function testTheExchangesToMakeConformOfAnOrganizationAreCountedAndListedInSlices(): void
    {
        $exchanges = $this->exchanges();
        $older = $this->noted('2026-10-10 09:00 UTC');
        $recent = $this->noted('2026-10-11 09:00 UTC');
        array_map($exchanges->save(...), [$older, $recent, $this->notedWithTheOrganization('2026-10-12 09:00 UTC')]);

        foreach ([$older, $recent] as $exchange) {
            $exchange->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
            $exchanges->save($exchange);
        }

        $first = $exchanges->toMakeConformFor($this->acme, null, 1);
        $following = $exchanges->toMakeConformFor($this->acme, $first->next, 1);
        $this->assertSame([2, 2], [$exchanges->countToMakeConformFor($this->acme), $exchanges->countToMakeConformFor($this->group)]);
        $this->assertEquals([[$recent->id()], [$older->id()]], [array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $first->content), array_map(static fn (Exchange $exchange): ExchangeId => $exchange->id(), $following->content)]);
        $this->assertTrue($exchanges->get($older->id())->isToMakeConform());
    }

    public function testAnExchangeMadeConformIsNoLongerCounted(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchange->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $exchange->declareConform(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $this->assertSame(0, $exchanges->countToMakeConformFor($this->acme));
        $this->assertSame([], $exchanges->toMakeConformFor($this->acme, null, 20)->content);
        $this->assertEquals(new Correction($this->prospector->id, new \DateTimeImmutable(self::NOW)), $exchanges->get($exchange->id())->madeConform());
    }

    public function testAnExchangeNeverNotedIsUnknown(): void
    {
        $this->expectException(UnknownExchange::class);

        $this->exchanges()->get(new ExchangeId(self::newIdentity()));
    }

    private function noted(string $occurredAt): Exchange
    {
        return Exchange::notedWithAContact($this->prospector->accountId, $this->prospector->id, new ExchangeId(self::newIdentity()), $this->contact, [$this->acme, $this->group], new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt), 'Rappeler lundi'), new \DateTimeImmutable(self::NOW));
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }
}
