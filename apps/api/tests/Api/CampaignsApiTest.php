<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The campaigns a team prepares (ADR 0144): only with the right to manage personal data, as every gesture needing the
 * contact details of the contacts (ADR 0081).
 */
#[Group('api')]
class CampaignsApiTest extends ApiTestCase
{
    use ReadsResources;

    private const string TAG = '#CAMPAIGN_151';

    private string $ada;
    private string $grace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [self::TAG]]);
        $this->ada = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'jobTitle' => 'CTO', 'contactDetails' => ['email' => 'ada@acme.test'], 'organizationIds' => [$organization]]), 'id');
        $this->grace = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'organizationIds' => [$organization]]), 'id');
    }

    public function testACampaignIsPreparedAsADraftTargetingTheContactsItsChannelReaches(): void
    {
        $campaign = $this->requestJson('POST', '/api/campaigns', $this->aCampaign());

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(
            ['Relance ESN', 'email', 'draft', [self::TAG], [self::TAG, '#EMAIL'], 'Bonjour {firstName}', '{firstName}, une question', 'asIs', 1],
            [$campaign['name'], $campaign['channel'], $campaign['status'], $campaign['tags'], $campaign['targetedTags'], $campaign['message'], $campaign['subject'], $campaign['messageMode'] ?? null, $campaign['targeted']],
        );
        $this->assertSame(['href' => '/api/tagged-contacts?tags=CAMPAIGN_151,EMAIL', 'method' => 'GET'], self::valueAt($campaign, '_links', 'target'));
        $self = '/api/campaigns/'.self::stringAt($campaign, 'id');
        $this->assertSame([
            'self' => ['href' => $self, 'method' => 'GET'],
            'revise' => ['href' => $self, 'method' => 'PATCH'],
            'discard' => ['href' => $self, 'method' => 'DELETE'],
            'freeze' => ['href' => $self.'/freeze', 'method' => 'POST'],
            'setDailyCap' => ['href' => $self.'/daily-cap', 'method' => 'PATCH'],
            'target' => ['href' => '/api/tagged-contacts?tags=CAMPAIGN_151,EMAIL', 'method' => 'GET'],
        ], self::recordAt($campaign, '_links'));
        $this->assertSame($campaign, $this->requestJson('GET', '/api/campaigns/'.self::stringAt($campaign, 'id')));
    }

    public function testTheCampaignsOfTheTeamAreListedWithTheirAuthorAndTheirTargetCounted(): void
    {
        $campaign = $this->requestJson('POST', '/api/campaigns', $this->aCampaign());
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $listed = $this->requestJson('GET', '/api/campaigns');

        $this->assertResponseStatusCodeSame(200);
        $mine = array_find(self::recordsAt($listed, 'items'), static fn (array $item): bool => $item['id'] === $campaign['id']) ?? throw new \LogicException('The campaign is not listed');
        $this->assertSame(['Relance ESN', 'email', 'draft', ['processed' => 0, 'dismissed' => 0], 1], [$mine['name'], $mine['channel'], $mine['status'], $mine['progress'], $mine['targeted']]);
        $this->assertSame($campaign['author'], $mine['author']);
        $this->assertNull($mine['processedToday']);
        $this->assertSame(['self' => ['href' => '/api/campaigns', 'method' => 'GET'], 'prepare' => ['href' => '/api/campaigns', 'method' => 'POST']], self::recordAt($listed, '_links'));
    }

    public function testADraftIsRevisedThenDiscarded(): void
    {
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', $this->aCampaign()), 'id');

        $revised = $this->requestJson('PATCH', '/api/campaigns/'.$id, ['name' => 'Appels ESN', 'channel' => 'call', 'tags' => [self::TAG], 'message' => 'Pitch : {organization}', 'messageMode' => 'adapted']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['Appels ESN', 'call', [self::TAG, '#PHONE'], null, 'adapted', 0], [$revised['name'], $revised['channel'], $revised['targetedTags'], $revised['subject'], $revised['messageMode'], $revised['targeted']]);

        $this->requestJson('DELETE', '/api/campaigns/'.$id);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame('unknownCampaign', $this->requestJson('GET', '/api/campaigns/'.$id)['reason']);
    }

    public function testTheMessageIsRenderedForAContact(): void
    {
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', $this->aCampaign(message: 'Bonjour {firstName} {lastName}, {jobTitle} chez {organization}')), 'id');

        $message = $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->ada);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['subject' => 'Ada, une question', 'text' => 'Bonjour Ada Lovelace, CTO chez ACME'], $message);
        $this->assertSame('Bonjour Grace Hopper,  chez ACME', $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->grace)['text']);
    }

    public function testTheMessageAddressesAContactByTheirCivility(): void
    {
        $this->requestJson('PATCH', '/api/contacts/'.$this->grace.'/title', ['title' => 'ms']);
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', $this->aCampaign(message: 'Bonjour {title} {lastName},')), 'id');

        $this->assertSame('Bonjour Madame Hopper,', $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->grace)['text']);
        $this->assertSame('Bonjour  Lovelace,', $this->requestJson('GET', '/api/campaigns/'.$id.'/messages/'.$this->ada)['text']);
    }

    public function testTheMessageOfAPersonWhoIsNoContactOfTheAccountIsNotFound(): void
    {
        $id = self::stringAt($this->requestJson('POST', '/api/campaigns', $this->aCampaign()), 'id');

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
        yield 'a daily cap below one send' => [['dailyCap' => 0], 'dailyCap', 'invalidDailyCap'];
        yield 'the tag of the channel a sixth' => [['tags' => ['#A', '#B', '#C', '#D', '#E']], 'tags', 'invalidTagSelection'];
        yield 'a message too long' => [['message' => str_repeat('a', 5001)], 'message', 'messageTooLong'];
        yield 'a subject for a call' => [['channel' => 'call'], 'subject', 'subjectOnlyForAnEmail'];
        yield 'an unknown mode of the message for an AI' => [['messageMode' => 'rewritten'], 'messageMode', 'unknownMessageMode'];
    }

    public function testMyAccountLinksTheCampaignsOfMyTeam(): void
    {
        $this->assertSame(['href' => '/api/campaigns', 'method' => 'GET'], self::valueAt($this->requestJson('GET', '/api/me'), '_links', 'campaigns'));
    }

    public function testAnAdministratorIsLinkedToTheCampaignsOfTheTeam(): void
    {
        $this->login($this->aMemberOfTheFixtures('administrator', Role::Prospector, Role::Admin));

        $this->assertArrayHasKey('campaigns', self::recordAt($this->requestJson('GET', '/api/me'), '_links'));
    }

    public function testWithoutTheRightToManagePersonalDataNoCampaignIsLinked(): void
    {
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertArrayNotHasKey('campaigns', self::recordAt($this->requestJson('GET', '/api/me'), '_links'));
    }

    /**
     * @return array<string, mixed>
     */
    private function aCampaign(string $message = 'Bonjour {firstName}'): array
    {
        return ['name' => 'Relance ESN', 'channel' => 'email', 'tags' => [self::TAG], 'message' => $message, 'subject' => '{firstName}, une question'];
    }
}
