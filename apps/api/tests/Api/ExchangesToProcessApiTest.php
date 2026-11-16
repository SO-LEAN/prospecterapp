<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Repository\OrganizationRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges the signed-in member planned whose end has passed and that they have not handled yet, on their
 * dashboard; the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ExchangesToProcessApiTest extends ApiTestCase
{
    private const string TO_PROCESS = '/api/me/exchanges-to-process';

    private string $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
    }

    public function testTheExchangesIPlannedWhoseEndHasPassedAreToProcessTheOldestFirst(): void
    {
        $me = $this->memberIdOf('prospector-user');
        $yesterday = $this->plannedBy($me, '2026-10-14T16:00:00+00:00');
        $thisMorning = $this->plannedBy($me, '2026-10-15T08:00:00+00:00');
        $this->plannedBy($me, '2026-10-15T08:45:00+00:00');
        $this->requestJson('POST', '/api/organizations/'.$this->organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'meeting', 'occurredAt' => '2026-10-15T14:00:00+00:00']]);

        $toProcess = $this->requestJson('GET', self::TO_PROCESS);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[$yesterday, 'ACME'], [$thisMorning, 'ACME']], array_map(static fn (array $item): array => [$item['exchange']['id'], $item['interlocutor']['name']], $toProcess['items']));
        $this->assertSame(['href' => self::TO_PROCESS, 'method' => 'GET'], $toProcess['_links']['self']);
    }

    /**
     * The organization is erased at once, its exchanges by a reaction after it: in between, the exchanges to process
     * are read all the same, without them.
     */
    public function testAnExchangeWithAnOrganizationErasedBeforeItIsNoLongerToProcess(): void
    {
        $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-14T16:00:00+00:00');
        $this->eraseBeforeItsExchanges($this->organization);

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['items']);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testAnExchangeToProcessIsMarkedDoneOrMissedCancelledOrMovedFromTheList(): void
    {
        $passed = $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T08:00:00+00:00');

        $links = $this->requestJson('GET', self::TO_PROCESS)['items'][0]['exchange']['_links'];

        $this->assertSame([
            'move' => ['href' => '/api/exchanges/'.$passed.'/moment', 'method' => 'PATCH'],
            'markDone' => ['href' => '/api/exchanges/'.$passed.'/done', 'method' => 'POST'],
            'markMissed' => ['href' => '/api/exchanges/'.$passed.'/missed', 'method' => 'POST'],
            'cancel' => ['href' => '/api/exchanges/'.$passed.'/cancellation', 'method' => 'POST'],
        ], array_intersect_key($links, ['move' => true, 'markDone' => true, 'markMissed' => true, 'cancel' => true]));
    }

    public function testAnExchangeHandledIsNoLongerToProcess(): void
    {
        $done = $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T07:00:00+00:00');
        $missed = $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T08:00:00+00:00');

        $this->requestJson('POST', '/api/exchanges/'.$done.'/done', ['text' => 'Mission de 3 mois']);
        $this->requestJson('POST', '/api/exchanges/'.$missed.'/missed', []);

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['items']);
    }

    /**
     * Planned while they had the right to manage personal data, the exchange is still theirs to handle.
     */
    public function testAHolderWithoutTheRightToManagePersonalDataProcessesTheirExchanges(): void
    {
        $assistant = $this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector);
        $passed = $this->plannedBy($this->memberIdOf($assistant), '2026-10-15T08:00:00+00:00');
        $this->login($assistant);

        $items = $this->requestJson('GET', self::TO_PROCESS)['items'];

        $this->assertSame([$passed], array_column(array_column($items, 'exchange'), 'id'));
        $this->assertArrayHasKey('markDone', $items[0]['exchange']['_links']);
    }

    /**
     * Every role reads the exchanges they planned, never those of the others.
     */
    #[DataProvider('colleagues')]
    public function testEachMemberProcessesOnlyTheirOwnExchanges(string $reference, Role ...$roles): void
    {
        $this->plannedBy($this->memberIdOf('prospector-user'), '2026-10-15T08:00:00+00:00');
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->assertSame([], $this->requestJson('GET', self::TO_PROCESS)['items']);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function colleagues(): iterable
    {
        yield 'a prospector without the right to manage personal data' => ['prospector-without-the-right', Role::User, Role::Prospector];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    public function testAMemberWhoIsNotAProspectorHasNoExchangeToProcess(): void
    {
        $this->login($this->aMemberOfTheFixtures('assistant', Role::User));

        $this->requestJson('GET', self::TO_PROCESS);

        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * Planned the day before by the member, half an hour long, so that its time has come at the clock of the tests.
     */
    private function plannedBy(string $memberId, string $at): string
    {
        $facts = ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable($at), Duration::inMinutes(null), null);
        $planned = Exchange::plannedWithTheOrganization(self::getContainer()->get(OrganizationRepository::class)->get(new OrganizationId($this->organization)), new MemberId($memberId), new ExchangeId(self::newIdentity()), $facts, new \DateTimeImmutable('2026-10-13 09:00 UTC'));
        self::getContainer()->get(ExchangeRepository::class)->save($planned);

        return $planned->id()->value;
    }
}
