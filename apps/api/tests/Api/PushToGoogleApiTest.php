<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * The exchanges a member plans, pushed into their calendar « Prospecter » at Google, moved, cancelled and erased with
 * them (ADR 0127), by the reaction the delivery `sync` of the tests runs within the gesture; the clock of the tests
 * says it is 2026-10-15 09:00 UTC, Paris two hours ahead.
 */
#[Group('api')]
class PushToGoogleApiTest extends ApiTestCase
{
    private const string NEXT_TUESDAY = '2026-10-20T14:00:00+00:00';
    private const string CODE = 'a-code-of-google';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->login();
    }

    public function testAPlannedExchangeIsPushedIntoTheCalendarOfProspecter(): void
    {
        $this->connectGoogle();
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');

        $planned = $this->plan($ada, ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'link' => 'https://meet.google.com/abc-defg-hij'])['items'][0]['id'];

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([self::eventIdOf($planned) => [
            'summary' => 'Visio · Ada Lovelace (ACME)',
            'location' => 'https://meet.google.com/abc-defg-hij',
            'description' => 'https://front.test.prospecter.io/contacts/'.$ada,
            'start' => ['dateTime' => '2026-10-20T16:00:00+02:00', 'timeZone' => 'Europe/Paris'],
            'end' => ['dateTime' => '2026-10-20T16:30:00+02:00', 'timeZone' => 'Europe/Paris'],
            'reminders' => ['useDefault' => false, 'overrides' => [['method' => 'popup', 'minutes' => 10]]],
        ]], array_map(self::described(...), $this->google()->events()));
    }

    public function testAVideoCallPlannedAskingForALinkReceivesTheMeetLinkGoogleCreated(): void
    {
        $this->connectGoogle();

        $planned = $this->plan($this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace'), ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'linkRequested' => true])['items'][0]['id'];

        $this->assertResponseStatusCodeSame(201);
        $event = $this->google()->events()[self::eventIdOf($planned)] ?? [];
        $this->assertStringStartsWith('https://meet.google.com/', $event['hangoutLink'] ?? '');
        $this->assertSame($event['hangoutLink'], $event['location'] ?? null);
        $exchange = $this->requestJson('GET', '/api/exchanges/'.$planned);
        $this->assertSame([$event['hangoutLink'], false], [$exchange['link'], $exchange['linkAwaited']]);
    }

    public function testAVideoCallPlannedFromTheOrganizationAskingForALinkReceivesItToo(): void
    {
        $this->connectGoogle();
        $organization = $this->anOrganization('ACME');

        $this->planWith($organization, ['exchange' => ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'linkRequested' => true]]);

        $this->assertStringStartsWith('https://meet.google.com/', array_first($this->google()->events())['location'] ?? '');
    }

    public function testAVideoCallAskingForALinkAwaitsItWithoutAConnection(): void
    {
        $planned = $this->plan($this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace'), ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'linkRequested' => true])['items'][0]['id'];

        $exchange = $this->requestJson('GET', '/api/exchanges/'.$planned);
        $this->assertSame([null, true], [$exchange['link'], $exchange['linkAwaited']]);
    }

    public function testACallAskingForALinkIsRefused(): void
    {
        $this->plan($this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace'), ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY, 'linkRequested' => true]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnExchangeMovedIsMovedThenCancelledLeavesTheCalendar(): void
    {
        $this->connectGoogle();
        $planned = $this->plan($this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace'), ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY])['items'][0]['id'];

        $this->requestJson('PATCH', '/api/exchanges/'.$planned.'/moment', ['occurredAt' => '2026-10-21T07:00:00+00:00']);

        $this->assertResponseIsSuccessful();
        $this->assertSame(['2026-10-21T09:00:00+02:00'], array_map(static fn (array $event): string => $event['start']['dateTime'], array_values($this->google()->events())));

        $this->requestJson('POST', '/api/exchanges/'.$planned.'/cancellation', []);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->google()->events());
    }

    public function testAnExchangeErasedWithItsOrganizationLeavesTheCalendar(): void
    {
        $this->connectGoogle();
        $organization = $this->anOrganization('ACME');
        $this->planWith($organization, ['exchange' => ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY, 'place' => 'Café de Flore']]);
        $this->assertSame(['Rendez-vous · ACME'], array_map(static fn (array $event): string => $event['summary'], array_values($this->google()->events())));

        $this->requestJson('DELETE', '/api/organizations/'.$organization);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $this->google()->events());
    }

    public function testATokenGoogleRefusesMakesTheConnectionToReconnectWithoutPushing(): void
    {
        $this->connectGoogle();
        $this->google()->refusesTheTokens();

        $this->plan($this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace'), ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([], $this->google()->events());
        $this->assertSame(['toReconnect'], array_column($this->requestJson('GET', '/api/me/connections')['connections'], 'state'));
    }

    public function testWithoutAConnectionNothingIsPushed(): void
    {
        $this->plan($this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace'), ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([], $this->google()->events());
    }

    private function connectGoogle(): void
    {
        parse_str((string) parse_url($this->requestJson('POST', '/api/me/connections/google/authorizations')['address'], \PHP_URL_QUERY), $query);
        $this->google()->grants(self::CODE, 'michel@exemple.fr', 'a-refresh-token');
        $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE, 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }

    /**
     * @param array<string, mixed> $event
     *
     * @return array<string, mixed>
     */
    private static function described(array $event): array
    {
        return array_intersect_key($event, array_flip(['summary', 'location', 'description', 'start', 'end', 'reminders']));
    }

    private static function eventIdOf(string $exchange): string
    {
        return 'p'.str_replace('-', '', $exchange);
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
     */
    private function planWith(string $organization, array $planned): void
    {
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', $planned);
        $this->assertResponseStatusCodeSame(201);
    }

    private function aContactOf(string $organization, string $firstName, string $lastName): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'organizationIds' => [$organization]])['id'];
    }

    private function anOrganization(string $name): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]])['id'];
    }

    private function google(): GoogleDouble
    {
        return self::getContainer()->get(GoogleDouble::class);
    }
}
