<?php

namespace Tests\App\Prospecting;

use App\Shared\Persistence\Hydrator;
use Prospecter\Core\Prospecting\Domain\Exception\UnknownExchange;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Correction;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges kept for an organization and those with a contact, from the items the repository writes next
 * to them (ADR 0074, 0076); their timelines are read by `DynamoTimelineRetriever`.
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

    public function testAnExchangeIsFoundByTheOrganizationsItIsKeptFor(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchange->stopKeepingFor($this->acme, by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $this->assertSame([], $exchanges->keptFor($this->acme));
        $this->assertEquals([$exchange->id()], array_map(static fn (Exchange $kept): ExchangeId => $kept->id(), $exchanges->keptFor($this->group)));
        $this->assertEquals([$this->group], Hydrator::stateOf($exchanges->keptFor($this->group)[0])['keptFor']);
    }

    public function testARemovedExchangeIsFoundNowhere(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchanges->save($exchange);

        $exchanges->remove($exchange);

        $this->assertSame([], $exchanges->keptFor($this->acme));
        $this->assertSame([], $exchanges->keptFor($this->group));
        $this->assertSame([], $exchanges->withContact($this->contact, $this->prospector->accountId));
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
        $this->assertEquals($corrected, Hydrator::stateOf($read)['facts']);
        $this->assertEquals(new Correction($this->prospector->id, new \DateTimeImmutable(self::NOW)), Hydrator::stateOf($read)['lastCorrection']);
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

    public function testAnExchangeMadeConformIsReadWithWhoDeclaredIt(): void
    {
        $exchanges = $this->exchanges();
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchange->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $exchange->declareConform(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $exchanges->save($exchange);

        $read = $exchanges->get($exchange->id());
        $this->assertEquals(new Correction($this->prospector->id, new \DateTimeImmutable(self::NOW)), Hydrator::stateOf($read)['madeConform']);
        $this->assertFalse($read->isToMakeConform());
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
