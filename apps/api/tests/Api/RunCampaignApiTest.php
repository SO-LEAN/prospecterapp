<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignBrief;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * A started campaign led recipient after recipient (ADR 0145): the exchange noted for one in the campaign processes
 * them, through the facts Prospecting publishes, or they are dismissed, the campaign ended once nobody is left to
 * handle; with the right to manage personal data only (ADR 0081, 0129).
 */
#[Group('api')]
class RunCampaignApiTest extends ApiTestCase
{
    use ReadsResources;

    private const string TAG = '#CAMPAIGN_154';

    private string $ada;
    private string $alan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->ada = $this->aContactOf($organization, 'Ada', 'Lovelace', '+33612345678');
        $this->alan = $this->aContactOf($organization, 'Alan', 'Turing', '+33698765432');
    }

    public function testAPendingRecipientOfAStartedCampaignOffersToNoteTheirExchangeAndToDismissThem(): void
    {
        $id = $this->aStartedCampaign();

        $recipient = $this->recipient($id, $this->ada);

        $this->assertSame('pending', $recipient['status']);
        $this->assertSame(['href' => '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', 'method' => 'POST'], self::valueAt($recipient, '_links', 'noteExchange'));
        $this->assertSame(['href' => '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/dismissal', 'method' => 'POST'], self::valueAt($recipient, '_links', 'dismiss'));
        $this->assertSame(['processed' => 0, 'dismissed' => 0], $this->requestJson('GET', '/api/campaigns/'.$id)['progress']);
    }

    public function testTheExchangeNotedForARecipientProcessesThemAndShowsItsCampaignInTheTimeline(): void
    {
        $id = $this->aStartedCampaign();

        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', ['occurredAt' => '2026-10-10T09:00:00+00:00', 'text' => 'Intéressée', 'missed' => true]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertMatchesRegularExpression('#^/api/exchanges/[0-9a-f-]{36}$#', (string) $this->client->getResponse()->headers->get('Location'));
        $recipient = $this->recipient($id, $this->ada);
        $this->assertSame(['processed', true, null, ['exchange']], [$recipient['status'], $recipient['missed'], $recipient['reason'], array_keys(self::recordAt($recipient, '_links'))]);
        $this->assertSame(['processed' => 1, 'dismissed' => 0], $this->requestJson('GET', '/api/campaigns/'.$id)['progress']);
        $exchange = self::recordAt($this->requestJson('GET', '/api/contacts/'.$this->ada.'/exchanges'), 'items', 0);
        $this->assertSame(['call', 'outbound', 'missed', ['id' => $id, 'name' => 'Appels ESN']], [$exchange['channel'], $exchange['direction'], $exchange['status'], $exchange['campaign']]);
    }

    public function testWhereTheExchangeOfARecipientWasSentIsThatOfTheChannelOfTheCampaign(): void
    {
        $id = $this->aStartedCampaign();

        $problem = $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', ['messageId' => '<relance@solean-it.test>']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['messageId'], 'referenceNotOfTheChannel'], [$this->violatedFields($problem), $problem['reason']]);
    }

    public function testDismissingTheLastRecipientToHandleEndsTheCampaign(): void
    {
        $id = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', []);

        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->alan.'/dismissal', ['reason' => 'Parti de la société']);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame(['dismissed', 'Parti de la société'], [$this->recipient($id, $this->alan)['status'], $this->recipient($id, $this->alan)['reason']]);
        $campaign = $this->requestJson('GET', '/api/campaigns/'.$id);
        $this->assertSame(['ended', ['processed' => 1, 'dismissed' => 1], ['self', 'recipients']], [$campaign['status'], $campaign['progress'], array_keys(self::recordAt($campaign, '_links'))]);
        $this->assertNotNull($campaign['endedAt']);
    }

    public function testARecipientHandledAlreadyIsRefusedASecondExchangeOrDismissal(): void
    {
        $id = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', []);

        $this->assertSame('recipientAlreadyProcessed', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', [])['reason']);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('recipientAlreadyProcessed', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/dismissal', [])['reason']);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testAStoppedCampaignIsNotRun(): void
    {
        $id = $this->aStartedCampaign();
        $this->requestJson('POST', '/api/campaigns/'.$id.'/stop');

        $this->assertSame('campaignNotRunning', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', [])['reason']);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('campaignNotRunning', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/dismissal', [])['reason']);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testAContactWhoIsNoRecipientIsNotFound(): void
    {
        $id = $this->aStartedCampaign();
        $initech = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Initech']]), 'id');
        $grace = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'organizationIds' => [$initech]]), 'id');

        $this->assertSame('unknownRecipient', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$grace.'/exchanges', [])['reason']);
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownRecipient', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$grace.'/dismissal', [])['reason']);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnExchangeOfTheFutureIsRefused(): void
    {
        $id = $this->aStartedCampaign();

        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', ['occurredAt' => '2099-01-01T09:00:00+00:00']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testACampaignOfAnotherAccountIsNotFound(): void
    {
        $id = $this->aCampaignOfAnotherAccount();

        $this->assertSame('unknownCampaign', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', [])['reason']);
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownCampaign', $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/dismissal', [])['reason']);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnAdministratorRunsACampaign(): void
    {
        $id = $this->aStartedCampaign();
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', []);
        $this->assertResponseStatusCodeSame(201);
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->alan.'/dismissal', []);
        $this->assertResponseStatusCodeSame(204);
    }

    public function testWithoutTheRightToManagePersonalDataNoCampaignIsRun(): void
    {
        $id = $this->aStartedCampaign();
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->ada.'/exchanges', []);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('POST', '/api/campaigns/'.$id.'/recipients/'.$this->alan.'/dismissal', []);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @return array<string, mixed>
     */
    private function recipient(string $campaign, string $contact): array
    {
        $recipients = self::recordsAt($this->requestJson('GET', '/api/campaigns/'.$campaign.'/recipients'), 'items');

        return array_find($recipients, static fn (array $recipient): bool => $contact === self::valueAt($recipient, 'contact', 'id')) ?? throw new \LogicException('No such recipient');
    }

    private function aStartedCampaign(): string
    {
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', ['name' => 'Appels ESN', 'channel' => 'call', 'tags' => [self::TAG], 'message' => 'Bonjour {firstName}', 'subject' => null]), 'id');
        $this->requestJson('POST', '/api/campaigns/'.$id.'/freeze');
        $this->assertResponseStatusCodeSame(200);
        $this->requestJson('POST', '/api/campaigns/'.$id.'/start');
        $this->assertResponseStatusCodeSame(200);

        return $id;
    }

    private function aCampaignOfAnotherAccount(): string
    {
        $competitor = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Competitor'), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS));
        self::service(AccountRepository::class)->save($competitor);
        $campaign = Campaign::prepare(
            new Gesture($competitor->id(), new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)),
            new CampaignId(self::newIdentity()),
            new CampaignBrief(CampaignName::of('Leurs appels'), Targeting::by(Channel::Call, [self::TAG]), MessageTemplate::of('Bonjour', null, Channel::Call)),
        );
        self::service(CampaignRepository::class)->save($campaign);

        return $campaign->id()->value;
    }

    private function aContactOf(string $organization, string $firstName, string $lastName, string $phone): string
    {
        return self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => ['phoneNumber' => $phone], 'organizationIds' => [$organization]]), 'id');
    }
}
