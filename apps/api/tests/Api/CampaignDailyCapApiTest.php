<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * The daily cap of a campaign, set as it is prepared and changed until it is stopped, and its recipients processed
 * today counted on the reader's clock; with the right to manage personal data only (ADR 0081).
 */
#[Group('api')]
class CampaignDailyCapApiTest extends ApiTestCase
{
    private const string TAG = '#CAMPAIGN_218';

    private string $ada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$organization]])['id'];
    }

    public function testACampaignIsPreparedWithItsDailyCap(): void
    {
        $campaign = $this->requestJson('POST', '/api/campaigns', [...$this->aCampaign(), 'dailyCap' => 30]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(30, $campaign['dailyCap']);
    }

    /**
     * @return array<string, mixed>
     */
    private function aCampaign(): array
    {
        return ['name' => 'Relance ESN', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {firstName}', 'subject' => null];
    }
}
