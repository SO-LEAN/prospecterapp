<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * A recipient processed links the exchange noted for them, and the exchange its campaign (ADR 0145); erasing that
 * exchange brings the recipient back to handle, an ended campaign led again (ADR 0152).
 */
#[Group('api')]
class CampaignExchangesApiTest extends ApiTestCase
{
    private const string TAG = '#CAMPAIGN_155';

    private string $ada;
    private string $alan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->ada = $this->aContactOf($organization, 'Ada', 'Lovelace', 'ada@acme.test');
        $this->alan = $this->aContactOf($organization, 'Alan', 'Turing', 'alan@acme.test');
    }

    public function testARecipientProcessedLinksTheExchangeNotedForThem(): void
    {
        $campaign = $this->aStartedCampaign();

        $exchange = $this->anExchangeNotedFor($campaign, $this->ada);

        $this->assertSame(['exchange' => ['href' => $exchange, 'method' => 'GET']], $this->recipient($campaign, $this->ada)['_links']);
    }

    public function testTheExchangeOfARecipientNamesItsCampaignAndLinksIt(): void
    {
        $campaign = $this->aStartedCampaign();

        $exchange = $this->requestJson('GET', $this->anExchangeNotedFor($campaign, $this->ada));

        $this->assertSame([['id' => $campaign, 'name' => 'Relance'], ['href' => '/api/campaigns/'.$campaign, 'method' => 'GET']], [$exchange['campaign'], $exchange['_links']['campaign']]);
    }

    public function testAnAdministratorGoesFromTheRecipientToTheExchangeAndBack(): void
    {
        $campaign = $this->aStartedCampaign();
        $path = $this->anExchangeNotedFor($campaign, $this->ada);
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->assertSame($path, $this->recipient($campaign, $this->ada)['_links']['exchange']['href']);
        $this->assertSame('/api/campaigns/'.$campaign, $this->requestJson('GET', $path)['_links']['campaign']['href']);
    }

    public function testWithoutTheRightToManagePersonalDataTheExchangeNamesItsCampaignWithoutLinkingIt(): void
    {
        $campaign = $this->aStartedCampaign();
        $path = $this->anExchangeNotedFor($campaign, $this->ada);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $exchange = $this->requestJson('GET', $path);

        $this->assertSame(['id' => $campaign, 'name' => 'Relance'], $exchange['campaign']);
        $this->assertArrayNotHasKey('campaign', $exchange['_links']);
    }

    public function testAnExchangeOfACampaignErasedByTheOperationsNamesNoCampaignNorLinksIt(): void
    {
        $campaign = $this->aStartedCampaign();
        $path = $this->anExchangeNotedFor($campaign, $this->ada);
        new CommandTester(new Application(self::$kernel)->find('app:campaign:erase'))->execute(['id' => $campaign]);

        $exchange = $this->requestJson('GET', $path);

        $this->assertSame(['id' => $campaign, 'name' => null], $exchange['campaign']);
        $this->assertArrayNotHasKey('campaign', $exchange['_links']);
    }

    public function testErasingTheExchangeOfARecipientBringsThemBackToHandleAndLeadsAnEndedCampaignAgain(): void
    {
        $campaign = $this->aStartedCampaign();
        $path = $this->anExchangeNotedFor($campaign, $this->ada);
        $this->requestJson('POST', '/api/campaigns/'.$campaign.'/recipients/'.$this->alan.'/dismissal', []);
        $this->assertSame('ended', $this->requestJson('GET', '/api/campaigns/'.$campaign)['status']);

        $this->requestJson('DELETE', $path);

        $this->assertResponseStatusCodeSame(204);
        $recipient = $this->recipient($campaign, $this->ada);
        $this->assertSame(['pending', ['noteExchange', 'dismiss']], [$recipient['status'], array_keys($recipient['_links'])]);
        $read = $this->requestJson('GET', '/api/campaigns/'.$campaign);
        $this->assertSame(['started', null, ['processed' => 0, 'dismissed' => 1]], [$read['status'], $read['endedAt'] ?? null, $read['progress']]);
    }

    public function testAnExchangeOfATimelineLinksItsCampaign(): void
    {
        $campaign = $this->aStartedCampaign();
        $this->anExchangeNotedFor($campaign, $this->ada);

        $this->assertSame(['href' => '/api/campaigns/'.$campaign, 'method' => 'GET'], $this->inTheTimelineOf($this->ada)['_links']['campaign']);
    }

    public function testWithoutTheRightToManagePersonalDataAnExchangeOfATimelineLinksNoCampaign(): void
    {
        $campaign = $this->aStartedCampaign();
        $this->anExchangeNotedFor($campaign, $this->ada);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $exchange = $this->inTheTimelineOf($this->ada);

        $this->assertSame(['id' => $campaign, 'name' => 'Relance'], $exchange['campaign']);
        $this->assertArrayNotHasKey('campaign', $exchange['_links']);
    }

    public function testAnExchangeOfATimelineLinksNoCampaignOnceErased(): void
    {
        $campaign = $this->aStartedCampaign();
        $this->anExchangeNotedFor($campaign, $this->ada);
        new CommandTester(new Application(self::$kernel)->find('app:campaign:erase'))->execute(['id' => $campaign]);

        $this->assertArrayNotHasKey('campaign', $this->inTheTimelineOf($this->ada)['_links']);
    }

    /**
     * @return array<string, mixed>
     */
    private function inTheTimelineOf(string $contact): array
    {
        return $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items'][0];
    }

    /**
     * @return array<string, mixed>
     */
    private function recipient(string $campaign, string $contact): array
    {
        $recipients = $this->requestJson('GET', '/api/campaigns/'.$campaign.'/recipients')['items'];

        return array_find($recipients, static fn (array $recipient): bool => $contact === $recipient['contact']['id']) ?? throw new \LogicException('No such recipient');
    }

    private function anExchangeNotedFor(string $campaign, string $contact): string
    {
        $this->requestJson('POST', '/api/campaigns/'.$campaign.'/recipients/'.$contact.'/exchanges', ['occurredAt' => '2026-10-10T09:00:00+00:00', 'text' => 'Relancée']);
        $this->assertResponseStatusCodeSame(201);

        return (string) self::getClient()->getResponse()->headers->get('Location');
    }

    private function aStartedCampaign(): string
    {
        $id = $this->requestJson('POST', '/api/campaigns', ['name' => 'Relance', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {prénom}', 'subject' => 'Une question'])['id'];
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/start');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    private function aContactOf(string $organization, string $firstName, string $lastName, string $email): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => ['email' => $email], 'organizationIds' => [$organization]])['id'];
    }
}
