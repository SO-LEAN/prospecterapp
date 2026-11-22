<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The daily cap of a campaign, set as it is prepared and changed until it is stopped, and its recipients processed
 * today counted on the reader's clock; with the right to manage personal data only (ADR 0081).
 */
#[Group('api')]
class CampaignDailyCapApiTest extends ApiTestCase
{
    use ReadsResources;

    private const string TAG = '#CAMPAIGN_218';

    private string $ada;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->ada = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$organization]]), 'id');
    }

    public function testACampaignIsPreparedWithItsDailyCap(): void
    {
        $campaign = $this->requestJson('POST', '/api/campaigns', [...$this->aCampaign(), 'dailyCap' => 30]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(30, $campaign['dailyCap']);
    }

    public function testTheDailyCapOfAStartedCampaignIsSetThenTakenOff(): void
    {
        $id = $this->aStartedCampaign();

        $capped = $this->requestJson('PUT', '/api/campaigns/'.$id.'/daily-cap', ['dailyCap' => 2]);
        $this->assertResponseStatusCodeSame(200);
        $uncapped = $this->requestJson('PUT', '/api/campaigns/'.$id.'/daily-cap', ['dailyCap' => null]);

        $this->assertSame([2, null], [$capped['dailyCap'], $uncapped['dailyCap']]);
    }

    public function testTheDailyCapIsSetUntilTheCampaignIsStopped(): void
    {
        $id = $this->aStartedCampaign();
        $started = $this->requestJson('GET', '/api/campaigns/'.$id);

        $stopped = $this->requestJson('POST', '/api/campaigns/'.$id.'/stop');
        $refusal = $this->requestJson('PUT', '/api/campaigns/'.$id.'/daily-cap', ['dailyCap' => 2]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame(
            [['href' => '/api/campaigns/'.$id.'/daily-cap', 'method' => 'PUT'], false, 'startedCampaign'],
            [self::valueAt($started, '_links', 'setDailyCap'), array_key_exists('setDailyCap', self::record(self::valueAt($stopped, '_links'))), $refusal['reason']],
        );
    }

    public function testTheCampaignCountsItsRecipientsProcessedTodayOnTheReadersClock(): void
    {
        $id = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', ['occurredAt' => '2026-10-15T08:00:00+00:00', 'text' => 'Bonjour Ada', 'missed' => false]);
        $this->assertResponseStatusCodeSame(201);

        $campaign = $this->requestJson('GET', '/api/campaigns/'.$id.'?timeZone=Europe/Paris');

        $this->assertSame(1, $campaign['processedToday']);
    }

    public function testTheCampaignIsNotReadOnAnUnknownClock(): void
    {
        $id = $this->aStartedCampaign();

        $this->requestJson('GET', '/api/campaigns/'.$id.'?timeZone=Mars/Olympus');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testAnAdministratorSetsTheDailyCap(): void
    {
        $id = $this->aStartedCampaign();
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->requestJson('PUT', '/api/campaigns/'.$id.'/daily-cap', ['dailyCap' => 2]);

        $this->assertResponseStatusCodeSame(200);
    }

    public function testWithoutTheRightToManagePersonalDataTheDailyCapIsNotSet(): void
    {
        $id = $this->aStartedCampaign();
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->requestJson('PUT', '/api/campaigns/'.$id.'/daily-cap', ['dailyCap' => 2]);

        $this->assertResponseStatusCodeSame(403);
    }

    private function aStartedCampaign(): string
    {
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', $this->aCampaign()), 'id');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/start');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function aCampaign(): array
    {
        return ['name' => 'Relance ESN', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour {firstName}', 'subject' => null];
    }
}
