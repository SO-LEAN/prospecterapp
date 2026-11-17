<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;

/**
 * A frozen campaign started, then stopped: it no longer changes, is never discarded, and stays listed, with the right
 * to manage personal data only (ADR 0081, 0144).
 */
#[Group('api')]
class StartedCampaignsApiTest extends ApiTestCase
{
    private const string TAG = '#CAMPAIGN_153';

    private string $ada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->ada = $this->aContactTagged('Ada', 'Lovelace', 'ACME', 'ada@acme.test');
    }

    public function testAFrozenCampaignStartsThenOffersOnlyToBeStopped(): void
    {
        $id = $this->aFrozenCampaign();
        $frozen = $this->requestJson('GET', '/api/campaigns/'.$id);
        $this->assertSame(['href' => '/api/campaigns/'.$id.'/starting', 'method' => 'POST'], $frozen['_links']['start']);

        $started = $this->requestJson('POST', '/api/campaigns/'.$id.'/starting');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['started', null], [$started['status'], $started['stoppedAt']]);
        $this->assertNotNull($started['startedAt']);
        $this->assertSame(['self', 'stop', 'recipients'], array_keys($started['_links']));
        $this->assertSame(['href' => '/api/campaigns/'.$id.'/stopping', 'method' => 'POST'], $started['_links']['stop']);
        $this->assertSame([['noteExchange', 'dismiss']], array_map(static fn (array $recipient): array => array_keys($recipient['_links']), $this->requestJson('GET', '/api/campaigns/'.$id.'/recipients')['items']));
    }

    public function testAStartedCampaignNoLongerChangesAndIsNotDiscarded(): void
    {
        $id = $this->aStartedCampaign();

        $this->assertSame('startedCampaign', $this->requestJson('DELETE', '/api/campaigns/'.$id.'/recipients/'.$this->ada)['reason']);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('startedCampaign', $this->requestJson('PATCH', '/api/campaigns/'.$id, $this->aCampaign())['reason']);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('startedCampaignNotDiscarded', $this->requestJson('DELETE', '/api/campaigns/'.$id)['reason']);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testADraftDoesNotStart(): void
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];

        $problem = $this->requestJson('POST', '/api/campaigns/'.$id.'/starting');

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('campaignNotFrozen', $problem['reason']);
    }

    public function testAStoppedCampaignStaysListedAndDoesNotStartAgain(): void
    {
        $id = $this->aStartedCampaign();

        $stopped = $this->requestJson('POST', '/api/campaigns/'.$id.'/stopping');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('stopped', $stopped['status']);
        $this->assertNotNull($stopped['stoppedAt']);
        $this->assertSame(['self', 'recipients'], array_keys($stopped['_links']));
        $this->assertContains($id, array_column($this->requestJson('GET', '/api/campaigns')['items'], 'id'));
        $this->assertSame('campaignNotFrozen', $this->requestJson('POST', '/api/campaigns/'.$id.'/starting')['reason']);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testAContactErasedStaysInAStartedCampaignWithoutItsNameNoLongerToHandle(): void
    {
        $id = $this->aStartedCampaign();

        $this->requestJson('DELETE', '/api/contacts/'.$this->ada);

        $recipients = $this->requestJson('GET', '/api/campaigns/'.$id.'/recipients')['items'];
        $this->assertSame([[null, 'erased']], array_map(static fn (array $recipient): array => [$recipient['contact'], $recipient['status']], $recipients));
        $this->assertSame(1, $this->requestJson('GET', '/api/campaigns/'.$id)['targeted']);
    }

    public function testAFrozenCampaignItsErasedContactsEmptiedNoLongerStartsAndIsStillDiscarded(): void
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('DELETE', '/api/contacts/'.$this->ada);

        $emptied = $this->requestJson('GET', '/api/campaigns/'.$id);
        $this->assertSame(['frozen', 0], [$emptied['status'], $emptied['targeted']]);
        $this->assertArrayNotHasKey('start', $emptied['_links']);
        $this->assertArrayHasKey('discard', $emptied['_links']);
        $this->assertSame('campaignWithoutRecipient', $this->requestJson('POST', '/api/campaigns/'.$id.'/starting')['reason']);
        $this->assertResponseStatusCodeSame(422);

        $this->requestJson('DELETE', '/api/campaigns/'.$id);
        $this->assertResponseIsSuccessful();
        $this->assertNotContains($id, array_column($this->requestJson('GET', '/api/campaigns')['items'], 'id'));
    }

    public function testAnAdministratorStartsAndStopsACampaign(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));
        $this->aContactTagged('Ada', 'Byron', 'Initech', 'ada@initech.test');
        $id = $this->aFrozenCampaign();

        foreach ($this->gesturesOn($id) as [$path, $status]) {
            $this->requestJson('POST', $path);
            $this->assertResponseStatusCodeSame($status, $path);
        }
    }

    public function testWithoutTheRightToManagePersonalDataACampaignIsNeitherStartedNorStopped(): void
    {
        $id = $this->aFrozenCampaign();
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        foreach ($this->gesturesOn($id) as [$path]) {
            $this->requestJson('POST', $path);
            $this->assertResponseStatusCodeSame(403, $path);
        }
    }

    /**
     * Starting, then stopping.
     *
     * @return list<array{string, int}>
     */
    private function gesturesOn(string $id): array
    {
        return [['/api/campaigns/'.$id.'/starting', 200], ['/api/campaigns/'.$id.'/stopping', 200]];
    }

    private function aStartedCampaign(): string
    {
        $id = $this->aFrozenCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/starting');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    private function aFrozenCampaign(): string
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freezing');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    private function aContactTagged(string $firstName, string $lastName, string $corporateName, string $email): string
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $corporateName]])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);

        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => ['email' => $email], 'organizationIds' => [$organization]])['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function aCampaign(): array
    {
        return ['name' => 'Relance ESN', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {prénom}', 'subject' => null];
    }
}
