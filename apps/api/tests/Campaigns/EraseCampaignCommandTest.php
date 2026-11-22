<?php

declare(strict_types=1);

namespace Tests\App\Campaigns;

use App\Shared\Typed;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

/**
 * `app:campaign:erase <id>` erases a campaign whatever its status, known by its identity alone: the cleaning of a test
 * or a request of a person, reserved to the operations, the application never deleting a started campaign.
 */
class EraseCampaignCommandTest extends ApiTestCase
{
    private const string TAG = '#CAMPAIGN_153_ERASE';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAStartedCampaignIsErased(): void
    {
        $id = $this->aStartedCampaign();

        $tester = $this->erase($id);

        $tester->assertCommandIsSuccessful();
        $this->assertStringContainsString($id, $tester->getDisplay());
        $this->requestJson('GET', '/api/campaigns/'.$id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnUnknownCampaignIsSaidSo(): void
    {
        $unknown = self::newIdentity();

        $tester = $this->erase($unknown);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString(sprintf('Campaign %s not found', $unknown), $tester->getDisplay());
    }

    private function erase(string $id): CommandTester
    {
        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:campaign:erase'));
        $tester->execute(['id' => $id]);

        return $tester;
    }

    private function aStartedCampaign(): string
    {
        $organization = Typed::string($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id']);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$organization]]);
        $id = Typed::string($this->requestJson('POST', '/api/campaigns', ['name' => 'Relance', 'channel' => 'email', 'tags' => [self::TAG], 'message' => 'Bonjour', 'subject' => null])['id']);
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/start');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }
}
