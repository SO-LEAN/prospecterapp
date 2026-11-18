<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Account;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Accounts\Domain\Repository\AccountRepository;
use Prospecter\Core\Campaigns\Domain\Model\Campaign;
use Prospecter\Core\Campaigns\Domain\Model\CampaignName;
use Prospecter\Core\Campaigns\Domain\Model\Channel;
use Prospecter\Core\Campaigns\Domain\Model\MessageTemplate;
use Prospecter\Core\Campaigns\Domain\Model\Targeting;
use Prospecter\Core\Campaigns\Domain\Repository\CampaignRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\CampaignId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;

/**
 * The campaigns a team prepares (ADR 0144): only with the right to manage personal data, as every gesture needing the
 * contact details of the contacts (ADR 0081).
 */
#[Group('api')]
class CampaignsApiTest extends ApiTestCase
{
    private const string TAG = '#CAMPAIGN_151';

    private string $ada;
    private string $grace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'jobTitle' => 'CTO', 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$organization]])['id'];
        $this->grace = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'organizationIds' => [$organization]])['id'];
    }

    public function testACampaignIsPreparedAsADraftTargetingTheContactsItsChannelReaches(): void
    {
        $campaign = $this->requestJson('POST', '/api/campaigns', $this->aCampaign());

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(
            ['Relance ESN', 'email', 'draft', [self::TAG], [self::TAG, '#EMAIL'], 'Bonjour {prénom}', '{prénom}, une question', 1],
            [$campaign['name'], $campaign['channel'], $campaign['status'], $campaign['tags'], $campaign['targetedTags'], $campaign['message'], $campaign['subject'], $campaign['targeted']],
        );
        $this->assertSame(['href' => '/api/tagged-contacts?tags=CAMPAIGN_151,EMAIL', 'method' => 'GET'], $campaign['_links']['target']);
        $this->assertSame(['self', 'revise', 'discard', 'freeze', 'target'], array_keys($campaign['_links']));
        $this->assertSame($campaign, $this->requestJson('GET', '/api/campaigns/'.$campaign['id']));
    }

    public function testTheCampaignsOfTheTeamAreListedWithTheirAuthorAndTheirTargetCounted(): void
    {
        $campaign = $this->requestJson('POST', '/api/campaigns', $this->aCampaign());
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $listed = $this->requestJson('GET', '/api/campaigns');

        $this->assertResponseStatusCodeSame(200);
        $mine = array_find($listed['items'], static fn (array $item): bool => $item['id'] === $campaign['id']);
        $this->assertSame(['Relance ESN', 'email', 'draft', ['processed' => 0, 'dismissed' => 0], 1], [$mine['name'], $mine['channel'], $mine['status'], $mine['progress'], $mine['targeted']]);
        $this->assertSame($campaign['author'], $mine['author']);
        $this->assertSame(['href' => '/api/campaigns', 'method' => 'POST'], $listed['_links']['prepare']);
    }

    public function testADraftIsRevisedThenDiscarded(): void
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];

        $revised = $this->requestJson('PATCH', '/api/campaigns/'.$id, ['name' => 'Appels ESN', 'channel' => 'call', 'tags' => [self::TAG], 'message' => 'Pitch : {organisation}']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['Appels ESN', 'call', [self::TAG, '#PHONE'], null, 0], [$revised['name'], $revised['channel'], $revised['targetedTags'], $revised['subject'], $revised['targeted']]);

        $this->requestJson('DELETE', '/api/campaigns/'.$id);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame('unknownCampaign', $this->requestJson('GET', '/api/campaigns/'.$id)['reason']);
    }

    public function testTheMessageIsRenderedForAContact(): void
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign(message: 'Bonjour {prénom} {nom}, {fonction} chez {organisation}'))['id'];

        $message = $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->ada);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['subject' => 'Ada, une question', 'text' => 'Bonjour Ada Lovelace, CTO chez ACME'], $message);
        $this->assertSame('Bonjour Grace Hopper,  chez ACME', $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->grace)['text']);
    }

    public function testTheMessageAddressesAContactByTheirCivility(): void
    {
        $this->requestJson('PATCH', '/api/contacts/'.$this->grace.'/civility', ['civility' => 'ms']);
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign(message: 'Bonjour {civilité} {nom},'))['id'];

        $this->assertSame('Bonjour Madame Hopper,', $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->grace)['text']);
        $this->assertSame('Bonjour  Lovelace,', $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->ada)['text']);
    }

    public function testTheMessageOfAPersonWhoIsNoContactOfTheAccountIsNotFound(): void
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];

        $problem = $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.self::newIdentity());

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $problem['reason']);
    }

    /**
     * @param array<string, mixed> $changed
     */
    #[DataProvider('refusedCampaigns')]
    public function testTheRulesOfACampaignAreThoseOfTheCore(array $changed, string $field, string $reason): void
    {
        $problem = $this->requestJson('POST', '/api/campaigns', [...$this->aCampaign(), ...$changed]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([[$field], $reason], [$this->violatedFields($problem), $problem['reason']]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, string}>
     */
    public static function refusedCampaigns(): iterable
    {
        yield 'without a name' => [['name' => ' '], 'name', 'campaignWithoutName'];
        yield 'a name too long' => [['name' => str_repeat('a', 101)], 'name', 'campaignNameTooLong'];
        yield 'an unknown channel' => [['channel' => 'fax'], 'channel', 'unknownChannel'];
        yield 'no tag' => [['tags' => []], 'tags', 'invalidTagSelection'];
        yield 'the tag of the channel a sixth' => [['tags' => ['#A', '#B', '#C', '#D', '#E']], 'tags', 'invalidTagSelection'];
        yield 'a message too long' => [['message' => str_repeat('a', 5001)], 'message', 'messageTooLong'];
        yield 'a subject for a call' => [['channel' => 'call'], 'subject', 'subjectOnlyForAnEmail'];
    }

    public function testMyAccountLinksTheCampaignsOfMyTeam(): void
    {
        $this->assertSame(['href' => '/api/campaigns', 'method' => 'GET'], $this->requestJson('GET', '/api/me')['_links']['campaigns']);
    }

    public function testAnAdministratorRunsTheCampaignsOfTheTeam(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->assertArrayHasKey('campaigns', $this->requestJson('GET', '/api/me')['_links']);
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];
        $this->assertResponseStatusCodeSame(201);
        $gestures = [
            ['GET', '/api/campaigns', null, 200], ['GET', '/api/campaigns/'.$id, null, 200],
            ['PATCH', '/api/campaigns/'.$id, $this->aCampaign(), 200], ['GET', '/api/campaigns/'.$id.'/messages/'.$this->ada, null, 200],
            ['DELETE', '/api/campaigns/'.$id, null, 204],
        ];

        foreach ($gestures as [$method, $path, $payload, $status]) {
            $this->requestJson($method, $path, $payload);
            $this->assertResponseStatusCodeSame($status, $method.' '.$path);
        }
    }

    public function testWithoutTheRightToManagePersonalDataNoCampaignIsRun(): void
    {
        $id = $this->requestJson('POST', '/api/campaigns', $this->aCampaign())['id'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertArrayNotHasKey('campaigns', $this->requestJson('GET', '/api/me')['_links']);
        $gestures = [
            ['GET', '/api/campaigns', null], ['POST', '/api/campaigns', $this->aCampaign()],
            ['GET', '/api/campaigns/'.$id, null], ['PATCH', '/api/campaigns/'.$id, $this->aCampaign()], ['DELETE', '/api/campaigns/'.$id, null],
            ['GET', '/api/campaigns/'.$id.'/messages/'.$this->ada, null],
        ];

        foreach ($gestures as [$method, $path, $payload]) {
            $this->requestJson($method, $path, $payload);
            $this->assertResponseStatusCodeSame(403, $method.' '.$path);
        }
    }

    public function testACampaignOfAnotherAccountIsNotFoundForEveryIntention(): void
    {
        $id = $this->aCampaignOfAnotherAccount();
        $intentions = [
            ['GET', '', null], ['PATCH', '', $this->aCampaign()], ['DELETE', '', null], ['GET', '/messages/'.$this->ada, null],
            ['POST', '/freezing', null], ['POST', '/starting', null], ['POST', '/stopping', null], ['GET', '/recipients', null], ['POST', '/recipients/by-tags', ['tags' => [self::TAG]]],
            ['POST', '/recipients', ['contactId' => $this->ada]], ['DELETE', '/recipients/'.$this->ada, null],
        ];

        foreach ($intentions as [$method, $path, $payload]) {
            $problem = $this->requestJson($method, '/api/campaigns/'.$id.$path, $payload);
            $this->assertResponseStatusCodeSame(404, $method.' '.$path);
            $this->assertSame('unknownCampaign', $problem['reason'], $method.' '.$path);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function aCampaign(string $message = 'Bonjour {prénom}'): array
    {
        return ['name' => 'Relance ESN', 'channel' => 'email', 'tags' => [self::TAG], 'message' => $message, 'subject' => '{prénom}, une question'];
    }

    private function aCampaignOfAnotherAccount(): string
    {
        $competitor = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Competitor'), new \DateTimeImmutable());
        self::getContainer()->get(AccountRepository::class)->save($competitor);
        $campaign = Campaign::prepare(
            new CampaignId(self::newIdentity()),
            $competitor->id(),
            CampaignName::of('Leur campagne'),
            Targeting::by(Channel::Email, ['#ESN']),
            MessageTemplate::of('Bonjour', null, Channel::Email),
            new MemberId(self::newIdentity()),
            new \DateTimeImmutable(),
        );
        self::getContainer()->get(CampaignRepository::class)->save($campaign);

        return $campaign->id()->value;
    }
}
