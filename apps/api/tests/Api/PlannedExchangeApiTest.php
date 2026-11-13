<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;

/**
 * An exchange planned with a contact or with an organization, in their exchanges to come, apart from the past
 * ones; the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class PlannedExchangeApiTest extends ApiTestCase
{
    private const string NEXT_TUESDAY = '2026-10-20T14:00:00+00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAnExchangePlannedWithAContactIsAmongTheExchangesToComeNotThePastOnes(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->requestJson('POST', '/api/contacts/'.$ada.'/exchanges', ['channel' => 'videoCall', 'direction' => 'outbound', 'occurredAt' => '2026-10-10T09:00:00+00:00']);

        $upcoming = $this->plan($ada, ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'text' => 'Démonstration']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/exchanges/'.$upcoming['items'][0]['id']);
        $this->assertSame([['videoCall', 'outbound', self::NEXT_TUESDAY, 'planned', 30, 'Démonstration', [$organization]]], array_map(self::described(...), $upcoming['items']));
        $this->assertSame(['self', 'noteExchange', 'planExchange'], array_keys($upcoming['_links']));
        $this->assertSame(['href' => '/api/contacts/'.$ada.'/upcoming-exchanges', 'method' => 'POST'], $upcoming['_links']['planExchange']);
        $this->assertSame($upcoming, $this->requestJson('GET', '/api/contacts/'.$ada.'/upcoming-exchanges'));
        $this->assertSame([['videoCall', 'outbound', '2026-10-10T09:00:00+00:00', 'done', null, null, [$organization]]], array_map(self::described(...), $this->requestJson('GET', '/api/contacts/'.$ada.'/exchanges')['items']));
    }

    public function testAPlannedExchangeIsReadInItsDetailNotCorrected(): void
    {
        $ada = $this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace');
        $planned = $this->plan($ada, ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY, 'durationInMinutes' => 60])['items'][0];

        $detail = $this->requestJson('GET', '/api/exchanges/'.$planned['id']);

        $this->assertSame(['planned', 60], [$detail['status'], $detail['durationInMinutes']]);
        $this->assertArrayNotHasKey('correctExchange', $detail['_links']);
        $this->assertArrayHasKey('correctText', $detail['_links']);
    }

    public function testTheRulesOfThePlanningAreThoseOfTheCore(): void
    {
        $ada = $this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace');

        $past = $this->plan($ada, ['channel' => 'call', 'occurredAt' => '2026-10-14T09:00:00+00:00']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['occurredAt'], 'plannedExchangeInThePast'], [$this->violatedFields($past), $past['reason']]);
        $sms = $this->plan($ada, ['channel' => 'sms', 'occurredAt' => self::NEXT_TUESDAY]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['channel'], 'channelNotPlanned'], [$this->violatedFields($sms), $sms['reason']]);
        $tooLong = $this->plan($ada, ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY, 'durationInMinutes' => 481]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['duration'], 'durationOutOfRange'], [$this->violatedFields($tooLong), $tooLong['reason']]);
    }

    public function testAnExchangePlannedFromAnOrganizationWithItselfIsAmongItsExchangesToCome(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->planWith($organization, ['personId' => $ada, 'exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-21T09:00:00+00:00']]);

        $upcoming = $this->planWith($organization, ['exchange' => ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY]]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([
            [['kind' => 'organization', 'id' => $organization, 'name' => 'ACME', 'presence' => null], 'meeting', self::NEXT_TUESDAY],
            [['kind' => 'person', 'id' => $ada, 'name' => 'Ada Lovelace', 'presence' => 'present'], 'call', '2026-10-21T09:00:00+00:00'],
        ], array_map(static fn (array $item): array => [$item['interlocutor'], $item['exchange']['channel'], $item['exchange']['occurredAt']], $upcoming['items']));
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/upcoming-exchanges', 'method' => 'POST'], $upcoming['_links']['planExchange']);
        $this->assertSame($upcoming, $this->requestJson('GET', '/api/organizations/'.$organization.'/upcoming-exchanges'));
        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges')['items']);
    }

    public function testMyPlannedExchangesOverlappingASlotAreNamedWithWhoTheyAreWith(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->plan($ada, ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY]);
        $this->planWith($organization, ['exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-20T14:20:00+00:00']]);
        $this->plan($ada, ['channel' => 'call', 'occurredAt' => '2026-10-20T14:45:00+00:00']);

        $overlapping = $this->requestJson('GET', '/api/me/planned-exchanges?startsAt='.urlencode('2026-10-20T14:15:00+00:00').'&durationInMinutes=30');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['Ada Lovelace', '2026-10-20T14:00:00+00:00', 30], ['ACME', '2026-10-20T14:20:00+00:00', 30]], array_map(static fn (array $item): array => [$item['interlocutor']['name'], $item['exchange']['occurredAt'], $item['exchange']['durationInMinutes']], $overlapping['items']));
    }

    public function testTheExchangesPlannedByAColleagueDoNotOverlapMine(): void
    {
        $ada = $this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace');
        $this->plan($ada, ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY]);
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->assertSame([], $this->requestJson('GET', '/api/me/planned-exchanges?startsAt='.urlencode(self::NEXT_TUESDAY))['items']);
    }

    public function testAnAdministratorPlansWithTheRightToManagePersonalData(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->assertArrayHasKey('planExchange', $this->requestJson('GET', '/api/contacts/'.$ada.'/upcoming-exchanges')['_links']);
        $this->assertArrayHasKey('planExchange', $this->requestJson('GET', '/api/organizations/'.$organization.'/upcoming-exchanges')['_links']);
        $this->assertResponseStatusCodeSame(200);
        $this->plan($ada, ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY]);
        $this->assertResponseStatusCodeSame(201);
        $this->planWith($organization, ['exchange' => ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY]]);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testWithoutTheRightTheExchangesToComeAreReadButNothingPlanned(): void
    {
        $organization = $this->anOrganization('ACME');
        $grace = $this->aContactOf($organization, 'Grace', 'Hopper');
        $this->plan($grace, ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY, 'text' => 'Grace présentera son équipe']);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $upcoming = $this->requestJson('GET', '/api/contacts/'.$grace.'/upcoming-exchanges');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([[true, 'planned']], array_map(static fn (array $exchange): array => [$exchange['textFlagged'], $exchange['status']], $upcoming['items']));
        $this->assertSame(['self'], array_keys($upcoming['_links']));
        $this->assertSame(['G***e H***r'], array_column(array_column($this->requestJson('GET', '/api/organizations/'.$organization.'/upcoming-exchanges')['items'], 'interlocutor'), 'name'));
        $this->assertArrayNotHasKey('planExchange', $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges')['_links']);
        $this->assertSame([], $this->requestJson('GET', '/api/me/planned-exchanges?startsAt='.urlencode(self::NEXT_TUESDAY))['items']);
        $this->assertResponseStatusCodeSame(200);
        $this->plan($grace, ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY]);
        $this->assertResponseStatusCodeSame(403);
        $this->planWith($organization, ['exchange' => ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY]]);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @param array<string, mixed> $exchange
     *
     * @return list<mixed>
     */
    private static function described(array $exchange): array
    {
        return [$exchange['channel'], $exchange['direction'], $exchange['occurredAt'], $exchange['status'], $exchange['durationInMinutes'], $exchange['text'], $exchange['organizationIds']];
    }

    /**
     * @param array<string, mixed> $planned
     *
     * @return array<string, mixed>
     */
    private function plan(string $contact, array $planned): array
    {
        return $this->requestJson('POST', '/api/contacts/'.$contact.'/upcoming-exchanges', $planned);
    }

    /**
     * @param array<string, mixed> $planned
     *
     * @return array<string, mixed>
     */
    private function planWith(string $organization, array $planned): array
    {
        return $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', $planned);
    }

    private function aContactOf(string $organization, string $firstName, string $lastName): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'organizationIds' => [$organization]])['id'];
    }

    private function anOrganization(string $name): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]])['id'];
    }
}
