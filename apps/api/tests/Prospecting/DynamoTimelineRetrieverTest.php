<?php

namespace Tests\App\Prospecting;

use Prospecter\Core\Prospecting\Application\ExchangeInTimeline;
use Prospecter\Core\Prospecting\Application\OrganizationSheet;
use Prospecter\Core\Prospecting\Application\Port\OrganizationSheetRetriever;
use Prospecter\Core\Prospecting\Application\Port\TimelineRetriever;
use Prospecter\Core\Prospecting\Application\Timeline;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Direction;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Model\Organization;
use Prospecter\Core\Prospecting\Domain\Model\Prospector;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Service\OrphanContacts;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The timeline of a contact by the moment of its exchanges, the timeline of an organization and those of its
 * exchanges to make conform, read from the indexes the repository writes, without restoring the exchanges
 * (ADR 0074, 0076, 0079, 0088).
 */
class DynamoTimelineRetrieverTest extends ApiTestCase
{
    private const string NOW = '2026-10-18 19:00 UTC';

    private Prospector $prospector;
    private PersonId $contact;
    private Organization $acme;
    private Organization $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prospector = new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity()), managesPersonalData: true);
        $this->contact = new PersonId(self::newIdentity());
        $this->acme = $this->prospectWithTheContact('ACME');
        $this->group = $this->prospectWithTheContact('Groupe ACME');
    }

    public function testTheTimelineGoesFromTheMostRecentMomentInSlices(): void
    {
        $older = $this->noted('2026-10-10 09:00 UTC');
        $recent = $this->noted('2026-10-15 14:30 UTC');
        $oldest = $this->noted('2026-10-01 08:00 UTC');

        $first = $this->timelines()->ofContact($this->contact, $this->prospector, null, 2);
        $following = $this->timelines()->ofContact($this->contact, $this->prospector, $first->next, 2);

        $this->assertSame([self::idOf($recent), self::idOf($older)], self::idsIn($first));
        $this->assertSame([self::idOf($oldest)], self::idsIn($following));
        $this->assertNull($following->next);
    }

    public function testAnotherAccountHasItsOwnTimeline(): void
    {
        $this->noted('2026-10-10 09:00 UTC');

        $this->assertSame([], $this->timelines()->ofContact($this->contact, new Prospector(new MemberId(self::newIdentity()), new AccountId(self::newIdentity())), null, 20)->exchanges);
    }

    public function testARemovedExchangeIsInNoTimeline(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');

        $this->exchanges()->remove($exchange);

        $this->assertSame([], $this->timelines()->ofContact($this->contact, $this->prospector, null, 20)->exchanges);
        $this->assertSame([], $this->timelineOf($this->acme)->exchanges);
    }

    public function testTheTimelineOfAnOrganizationGoesFromTheMostRecentMomentInSlices(): void
    {
        $withTheContact = $this->noted('2026-10-10 09:00 UTC');
        $withTheOrganization = $this->notedWithTheOrganization('2026-10-15 14:30 UTC');
        $oldest = $this->noted('2026-10-01 08:00 UTC');

        $first = $this->timelines()->ofOrganization($this->sheetOf($this->acme), $this->prospector, null, 2);
        $following = $this->timelines()->ofOrganization($this->sheetOf($this->acme), $this->prospector, $first->next, 2);

        $this->assertSame([self::idOf($withTheOrganization), self::idOf($withTheContact)], self::idsIn($first));
        $this->assertSame([self::idOf($oldest)], self::idsIn($following));
        $this->assertNull($following->next);
    }

    public function testAnExchangeWithTheOrganizationItselfIsInTheTimelineOfNoContact(): void
    {
        $this->notedWithTheOrganization('2026-10-15 14:30 UTC');

        $this->assertSame([null, null], [$this->timelineOf($this->acme)->exchanges[0]->personId, $this->timelineOf($this->acme)->exchanges[0]->contactPresence]);
        $this->assertSame([], $this->timelineOf($this->group)->exchanges);
        $this->assertSame([], $this->timelines()->ofContact($this->contact, $this->prospector, null, 20)->exchanges);
    }

    public function testAnExchangeNoLongerKeptForAnOrganizationLeavesItsTimeline(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');

        $exchange->stopKeepingFor($this->acme->id(), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        $this->assertSame([], $this->timelineOf($this->acme)->exchanges);
        $this->assertCount(1, $this->timelineOf($this->group)->exchanges);
    }

    public function testAContactWhoLeftTheOrganizationIsShownRemovedInItsTimeline(): void
    {
        $this->noted('2026-10-10 09:00 UTC');

        $this->acme->removeContact($this->contact, $this->prospector->id, new \DateTimeImmutable(self::NOW), self::getContainer()->get(OrphanContacts::class));
        $this->organizations()->save($this->acme);

        $this->assertSame('removed', $this->timelineOf($this->acme)->exchanges[0]->contactPresence);
        $this->assertSame('present', $this->timelineOf($this->group)->exchanges[0]->contactPresence);
    }

    public function testWithoutTheRightToManagePersonalDataTheTextIsFlagged(): void
    {
        $this->noted('2026-10-10 09:00 UTC');

        $read = $this->timelines()->ofContact($this->contact, new Prospector(new MemberId(self::newIdentity()), $this->prospector->accountId), null, 20)->exchanges[0];

        $this->assertSame([true, 'Rappeler lundi'], [$read->textFlagged, $read->text]);
        $this->assertFalse($this->timelines()->ofContact($this->contact, $this->prospector, null, 20)->exchanges[0]->textFlagged);
    }

    public function testACorrectedMomentMovesTheExchangeInTheTimelines(): void
    {
        $first = $this->noted('2026-10-10 09:00 UTC');
        $second = $this->noted('2026-10-11 09:00 UTC');
        $third = $this->notedWithTheOrganization('2026-10-11 10:00 UTC');

        $first->correct(new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-12 09:00 UTC')), by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($first);

        $this->assertSame([self::idOf($first), self::idOf($second)], self::idsIn($this->timelines()->ofContact($this->contact, $this->prospector, null, 20)));
        $this->assertSame([self::idOf($first), self::idOf($third), self::idOf($second)], self::idsIn($this->timelineOf($this->acme)));
    }

    public function testTheExchangesToMakeConformOfAnOrganizationAreCountedAndListedInSlices(): void
    {
        $older = $this->noted('2026-10-10 09:00 UTC');
        $recent = $this->noted('2026-10-11 09:00 UTC');
        $this->notedWithTheOrganization('2026-10-12 09:00 UTC');

        foreach ([$older, $recent] as $exchange) {
            $exchange->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
            $this->exchanges()->save($exchange);
        }

        $first = $this->timelines()->toMakeConformIn($this->sheetOf($this->acme), $this->prospector, null, 1);
        $following = $this->timelines()->toMakeConformIn($this->sheetOf($this->acme), $this->prospector, $first->next, 1);
        $this->assertSame([2, 2, 2], [$first->toMakeConform, $this->timelineOf($this->acme)->toMakeConform, $this->timelineOf($this->group)->toMakeConform]);
        $this->assertSame([[self::idOf($recent)], [self::idOf($older)]], [self::idsIn($first), self::idsIn($following)]);
        $this->assertSame([true, true, 'forgotten'], [$first->exchanges[0]->toMakeConform, $first->exchanges[0]->textToMakeConform, $first->exchanges[0]->contactPresence]);
    }

    public function testAnExchangeMadeConformIsNoLongerCounted(): void
    {
        $exchange = $this->noted('2026-10-10 09:00 UTC');
        $exchange->forgetItsContact(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        $exchange->declareConform(by: $this->prospector->id, at: new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        $this->assertSame(0, $this->timelineOf($this->acme)->toMakeConform);
        $this->assertSame([], $this->timelines()->toMakeConformIn($this->sheetOf($this->acme), $this->prospector, null, 20)->exchanges);
    }

    private function prospectWithTheContact(string $corporateName): Organization
    {
        $organization = Organization::prospectedBy($this->prospector, new OrganizationId(self::newIdentity()), new LegalIdentity($corporateName), new ContactDetails(), new \DateTimeImmutable(self::NOW));
        $organization->addContact($this->contact, $this->prospector->id, new \DateTimeImmutable(self::NOW));
        $this->organizations()->save($organization);

        return $organization;
    }

    private function noted(string $occurredAt): Exchange
    {
        $exchange = Exchange::notedWithAContact($this->prospector->accountId, $this->prospector->id, new ExchangeId(self::newIdentity()), $this->contact, [$this->acme->id(), $this->group->id()], new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt), 'Rappeler lundi'), new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function notedWithTheOrganization(string $occurredAt): Exchange
    {
        $exchange = Exchange::notedWithTheOrganization($this->acme, $this->prospector->id, new ExchangeId(self::newIdentity()), new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable($occurredAt)), new \DateTimeImmutable(self::NOW));
        $this->exchanges()->save($exchange);

        return $exchange;
    }

    private function timelineOf(Organization $organization): Timeline
    {
        return $this->timelines()->ofOrganization($this->sheetOf($organization), $this->prospector, null, 20);
    }

    private function sheetOf(Organization $organization): OrganizationSheet
    {
        return self::getContainer()->get(OrganizationSheetRetriever::class)->get($organization->id());
    }

    /**
     * @return list<string>
     */
    private static function idsIn(Timeline $timeline): array
    {
        return array_map(static fn (ExchangeInTimeline $exchange): string => $exchange->id, $timeline->exchanges);
    }

    private static function idOf(Exchange $exchange): string
    {
        return $exchange->id()->value;
    }

    private function timelines(): TimelineRetriever
    {
        return self::getContainer()->get(TimelineRetriever::class);
    }

    private function exchanges(): ExchangeRepository
    {
        return self::getContainer()->get(ExchangeRepository::class);
    }

    private function organizations(): OrganizationRepository
    {
        return self::getContainer()->get(OrganizationRepository::class);
    }
}
