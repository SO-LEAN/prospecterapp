<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Symfony\Component\Clock\MockClock;
use Tests\App\Base\ApiTestCase;

/**
 * A planned exchange marked done or missed once its time has come, moved or cancelled, by its holder only; the clock
 * of the tests says it is 2026-10-15 09:00 UTC, moved forward by a test on a client never rebooted.
 */
#[Group('api')]
class HandlePlannedExchangeApiTest extends ApiTestCase
{
    private const string NEXT_TUESDAY = '2026-10-20T14:00:00+00:00';

    private string $contact;
    private string $planned;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->login();
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
        $upcoming = $this->requestJson('POST', '/api/contacts/'.$this->contact.'/upcoming-exchanges', ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'durationInMinutes' => 45, 'text' => 'Démonstration']);
        $this->planned = $upcoming['items'][0]['id'];
    }

    public function testBeforeItsTimeItsHolderMovesOrCancelsIt(): void
    {
        $detail = $this->requestJson('GET', '/api/exchanges/'.$this->planned);

        $this->assertSame(['href' => '/api/exchanges/'.$this->planned.'/moment', 'method' => 'PATCH'], $detail['_links']['move']);
        $this->assertSame(['href' => '/api/exchanges/'.$this->planned.'/cancellation', 'method' => 'POST'], $detail['_links']['cancel']);
        $this->assertArrayNotHasKey('markDone', $detail['_links']);
        $this->assertArrayNotHasKey('markMissed', $detail['_links']);
        $tooSoon = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/done', []);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('notYetDue', $tooSoon['reason']);
    }

    public function testOnceItsTimeHasComeItsHolderMarksItDoneWithItsReport(): void
    {
        $this->theClockShows('2026-10-20 15:00 UTC');
        $this->assertSame(['markDone', 'markMissed'], array_values(array_intersect(['markDone', 'markMissed'], array_keys($this->requestJson('GET', '/api/exchanges/'.$this->planned)['_links']))));

        $done = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/done', ['occurredAt' => '2026-10-20T14:10:00+00:00', 'text' => 'Mission de 3 mois en mars']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['done', '2026-10-20T14:10:00+00:00', 'Mission de 3 mois en mars', 45], [$done['status'], $done['occurredAt'], $done['text'], $done['durationInMinutes']]);
        $this->assertArrayHasKey('correctExchange', $done['_links']);
        $this->assertSame([], $this->requestJson('GET', '/api/contacts/'.$this->contact.'/upcoming-exchanges')['items']);
        $this->assertSame([[$this->planned, 'done']], array_map(static fn (array $exchange): array => [$exchange['id'], $exchange['status']], $this->requestJson('GET', '/api/contacts/'.$this->contact.'/exchanges')['items']));
    }

    public function testMarkedDoneItsPlannedMomentStaysWhenNoneIsGiven(): void
    {
        $this->theClockShows('2026-10-20 15:00 UTC');

        $done = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/done', []);

        $this->assertSame(['done', self::NEXT_TUESDAY], [$done['status'], $done['occurredAt']]);
    }

    public function testMarkedDoneItIsNeverInTheFuture(): void
    {
        $this->theClockShows('2026-10-20 15:00 UTC');

        $future = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/done', ['occurredAt' => '2026-10-20T16:00:00+00:00']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['occurredAt'], 'exchangeInTheFuture'], [$this->violatedFields($future), $future['reason']]);
    }

    public function testOnceItsTimeHasComeItsHolderMarksItMissed(): void
    {
        $this->theClockShows('2026-10-20 15:00 UTC');

        $missed = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/missed');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('missed', $missed['status']);
    }

    public function testMovedItKeepsItsDuration(): void
    {
        $moved = $this->requestJson('PATCH', '/api/exchanges/'.$this->planned.'/moment', ['occurredAt' => '2026-10-21T09:00:00+00:00']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['planned', '2026-10-21T09:00:00+00:00', 45], [$moved['status'], $moved['occurredAt'], $moved['durationInMinutes']]);
        $this->assertSame(['2026-10-21T09:00:00+00:00'], array_column($this->requestJson('GET', '/api/contacts/'.$this->contact.'/upcoming-exchanges')['items'], 'occurredAt'));
        $this->assertSame(['2026-10-21T09:00:00+00:00'], array_column(array_column($this->requestJson('GET', '/api/me/planned-exchanges?startsAt='.urlencode('2026-10-21T09:15:00+00:00'))['items'], 'exchange'), 'occurredAt'));
    }

    public function testItIsMovedOnlyToAMomentToCome(): void
    {
        $past = $this->requestJson('PATCH', '/api/exchanges/'.$this->planned.'/moment', ['occurredAt' => '2026-10-14T09:00:00+00:00']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('plannedExchangeInThePast', $past['reason']);
        $this->requestJson('PATCH', '/api/exchanges/'.$this->planned.'/moment', []);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testCancelledItStaysInThePastSayingWhoCalledItOff(): void
    {
        $cancelled = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/cancellation', ['canceller' => 'contact']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['cancelled', 'contact', self::NEXT_TUESDAY], [$cancelled['status'], $cancelled['canceller'], $cancelled['occurredAt']]);
        $this->assertSame([], array_intersect(['move', 'cancel', 'markDone', 'markMissed', 'correctExchange'], array_keys($cancelled['_links'])));
        $this->assertSame([], $this->requestJson('GET', '/api/contacts/'.$this->contact.'/upcoming-exchanges')['items']);
        $this->assertSame([[$this->planned, 'cancelled', 'contact']], array_map(static fn (array $exchange): array => [$exchange['id'], $exchange['status'], $exchange['canceller']], $this->requestJson('GET', '/api/contacts/'.$this->contact.'/exchanges')['items']));
        $this->assertSame([], $this->requestJson('GET', '/api/me/planned-exchanges?startsAt='.urlencode(self::NEXT_TUESDAY))['items']);
    }

    public function testAnExchangeNoLongerToComeDoesNotChange(): void
    {
        $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/cancellation', []);

        $again = $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/cancellation', []);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('notUpcoming', $again['reason']);
    }

    public function testAnUnknownCancellerIsRefused(): void
    {
        $this->requestJson('POST', '/api/exchanges/'.$this->planned.'/cancellation', ['canceller' => 'someone']);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * Neither an administrator nor a prospector without the right to manage personal data handles the exchange a
     * colleague planned: nothing offered, every gesture refused.
     */
    #[DataProvider('colleagues')]
    public function testAColleagueDoesNotHandleIt(string $reference, Role ...$roles): void
    {
        $this->theClockShows('2026-10-20 15:00 UTC');
        $colleague = $this->aMemberOfTheFixtures($reference, ...$roles);
        $this->theTokensAreCheckedNow();
        $this->login($colleague);

        $detail = $this->requestJson('GET', '/api/exchanges/'.$this->planned);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([], array_intersect(['move', 'cancel', 'markDone', 'markMissed'], array_keys($detail['_links'])));
        foreach ([['POST', 'done', []], ['POST', 'missed', null], ['PATCH', 'moment', ['occurredAt' => '2026-10-21T09:00:00+00:00']], ['POST', 'cancellation', []]] as [$method, $gesture, $payload]) {
            $refused = $this->requestJson($method, '/api/exchanges/'.$this->planned.'/'.$gesture, $payload);
            $this->assertResponseStatusCodeSame(403, $gesture);
            $this->assertSame('notTheHolder', $refused['reason'], $gesture);
        }
    }

    /**
     * @return iterable<string, array{string, Role, Role}>
     */
    public static function colleagues(): iterable
    {
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
        yield 'a prospector without the right to manage personal data' => ['prospector-without-the-right', Role::User, Role::Prospector];
    }

    /**
     * The client never rebooted keeps the clock checking the tokens at the moment it was built: a token issued since
     * would be in its future.
     */
    private function theTokensAreCheckedNow(): void
    {
        $tokenClock = $this->client->getContainer()->get('app.token_clock');
        $this->assertInstanceOf(MockClock::class, $tokenClock);
        $tokenClock->modify(sprintf('@%d', time() + 1));
    }

    private function theClockShows(string $moment): void
    {
        $clock = $this->client->getContainer()->get('clock');
        $this->assertInstanceOf(MockClock::class, $clock);
        $clock->modify($moment);
    }
}
