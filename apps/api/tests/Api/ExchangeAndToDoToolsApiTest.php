<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Model\Exchange;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\Prospecting\Domain\Repository\ExchangeRepository;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;

/**
 * An AI plans, handles and corrects the exchanges, and notes, ticks off and erases the to-dos, as its member does on
 * the screens (ADR 0163).
 */
#[Group('api')]
class ExchangeAndToDoToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    /** The exchanges planned are handled by their holder only, the prospector here. */
    private const array HOLDER = ['prospector-user'];

    private const array FRIDAY = ['text' => 'Envoyer le CV', 'dueOn' => '2026-10-16', 'dueAt' => '10:00'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiPlansAVideoCallWithAContact(): void
    {
        $ada = $this->ada();
        $tomorrow = ['channel' => 'videoCall', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'durationInMinutes' => 30, 'text' => 'Démonstration'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('planifier-echange', ['id' => $ada, ...$tomorrow], 'POST', '/api/contacts/'.$ada.'/upcoming-exchanges', $tomorrow, self::PROSPECTORS);

        $this->assertSame([['videoCall', '2026-10-16T08:00:00+00:00', 'planned']], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['occurredAt'], $exchange['status']], array_slice($upcoming['items'], 0, 1)));
    }

    public function testTheAiPlansAMeetingWithAContactOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $ada = $this->ada();
        $meeting = ['channel' => 'meeting', 'occurredAt' => '2026-10-16T10:00:00+02:00', 'place' => '1 place Kléber, Strasbourg'];

        $upcoming = $this->assertTheToolActsAsItsEndpoint('planifier-echange-organisation', ['id' => $babbage, 'personId' => $ada, ...$meeting], 'POST', '/api/organizations/'.$babbage.'/upcoming-exchanges', ['exchange' => $meeting, 'personId' => $ada], self::PROSPECTORS);

        $this->assertSame(['meeting', '1 place Kléber, Strasbourg'], [$upcoming['items'][0]['exchange']['channel'], $upcoming['items'][0]['exchange']['place']]);
    }

    public function testTheAiMarksDoneACallToProcessWithItsReport(): void
    {
        $call = $this->aCallWithAdaPlannedYesterday();
        $report = ['text' => 'Intéressée, rappeler en janvier'];

        $done = $this->assertTheToolActsAsItsEndpoint('marquer-echange-fait', ['id' => $call, ...$report], 'POST', '/api/exchanges/'.$call.'/done', $report, self::HOLDER);

        $this->assertSame(['done', 'Intéressée, rappeler en janvier'], [$done['status'], $done['text']]);
        $this->assertSame([], $this->requestJson('GET', '/api/me/exchanges-to-process')['items']);
    }

    public function testTheAiNotesAToDoForAContact(): void
    {
        $ada = $this->ada();

        $noted = $this->assertTheToolActsAsItsEndpoint('noter-a-faire', ['id' => $ada, ...self::FRIDAY], 'POST', '/api/contacts/'.$ada.'/to-dos', self::FRIDAY, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame(['Envoyer le CV', '2026-10-16', $ada], [$noted['text'], $noted['dueOn'], $noted['contact']['id']]);
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/contacts?q=Lovelace')['items'][0]['id'];
    }

    /**
     * A call the prospector planned with Ada Lovelace yesterday, to process.
     */
    private function aCallWithAdaPlannedYesterday(): string
    {
        $call = Exchange::plannedWithAContact(
            $this->accountOfTheFixtures(),
            new MemberId($this->memberIdOf('prospector-user')),
            new ExchangeId(self::newIdentity()),
            new PersonId($this->ada()),
            [new OrganizationId($this->babbage())],
            ExchangeFacts::planned(Channel::Call, new \DateTimeImmutable('2026-10-14 09:00 UTC'), Duration::inMinutes(null), null),
            new \DateTimeImmutable('2026-10-13 09:00 UTC'),
        );
        self::getContainer()->get(ExchangeRepository::class)->save($call);

        return $call->id()->value;
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/organizations?q=Babbage')['items'][0]['id'];
    }
}
