<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;

/**
 * The contacts carrying every tag chosen, their own, inherited and automatic alike, newest first and counted
 * (Campaigns, then PersonalData reading them as their reader may).
 */
#[Group('api')]
class TaggedContactsApiTest extends ApiTestCase
{
    private string $organization;
    private string $ada;
    private string $grace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$this->organization.'/tags', ['tags' => ['#CAMPAIGN_149']]);
        $this->ada = $this->aContact('Ada', 'Lovelace', ['email' => 'ada@acme.test']);
        $this->grace = $this->aContact('Grace', 'Hopper', []);
    }

    public function testTheContactsCarryingEveryTagChosenAreFoundNewestFirstAndCounted(): void
    {
        $found = $this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$this->grace, $this->ada], array_column($found['items'], 'id'));
        $this->assertSame(2, $found['found']);
        $this->assertSame(['firstName' => 'Grace', 'lastName' => 'Hopper'], $found['items'][0]['name']);
        $this->assertSame(['ACME'], array_column($found['items'][0]['organizations'], 'name'));
        $this->assertSame(['#CAMPAIGN_149'], $found['items'][0]['tags']);
        $this->assertSame(['href' => '/api/tagged-contacts?tags=CAMPAIGN_149', 'method' => 'GET'], $found['_links']['self']);
        $this->assertArrayNotHasKey('next', $found['_links']);

        $both = $this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149,EMAIL');

        $this->assertSame([$this->ada], array_column($both['items'], 'id'));
        $this->assertSame(1, $both['found']);
    }

    public function testTheNextSliceStartsAfterTheCursor(): void
    {
        $after = $this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149&after='.$this->grace);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([$this->ada], array_column($after['items'], 'id'));
        $this->assertSame(2, $after['found']);
    }

    public function testAContactForgottenIsNoLongerFound(): void
    {
        $this->requestJson('DELETE', '/api/contacts/'.$this->ada);

        $this->assertSame([$this->grace], array_column($this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149')['items'], 'id'));
    }

    #[DataProvider('refusedSelections')]
    public function testASelectionOfTagsThatMakesNoSearchIsRefused(string $tags, string $reason): void
    {
        $problem = $this->requestJson('GET', '/api/tagged-contacts?tags='.$tags);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['tags'], $reason], [$this->violatedFields($problem), $problem['reason']]);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refusedSelections(): iterable
    {
        yield 'more than five tags' => ['A,B,C,D,E,F', 'invalidTagSelection'];
        yield 'no tag at all' => ['A,%23%21%3F', 'invalidTag'];
    }

    public function testNoTagGivenIsRefused(): void
    {
        $problem = $this->requestJson('GET', '/api/tagged-contacts');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['tags'], $this->violatedFields($problem));
    }

    public function testTheListOfTheContactsLeadsToTheSearchByTagsAndTheTagsProposedNameTheAutomaticOnes(): void
    {
        $this->assertSame(['href' => '/api/tagged-contacts', 'method' => 'GET'], $this->requestJson('GET', '/api/contacts')['_links']['searchByTags']);
        $this->assertSame(['#EMAIL', '#PHONE', '#SMS', '#LINKEDIN'], $this->requestJson('GET', '/api/tags')['automatic']);
    }

    /**
     * Tags are no personal data: every prospector searches by them, the contacts read as they may read them, their
     * names in clear (ADR 0129).
     *
     * @param list<Role> $roles
     */
    #[DataProvider('prospectors')]
    public function testEveryProspectorSearchesByTags(string $reference, array $roles, bool $masked): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $found = $this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149,EMAIL');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['firstName' => 'Ada', 'lastName' => 'Lovelace'], $found['items'][0]['name']);
        $this->assertSame($masked, $found['items'][0]['masked']);
        $this->assertSame($masked ? 'a***@***' : 'ada@acme.test', $found['items'][0]['contactDetails']['email']);
    }

    /**
     * @return iterable<string, array{string, list<Role>, bool}>
     */
    public static function prospectors(): iterable
    {
        yield 'a prospector with the right' => ['prospector-with-the-right', [Role::User, Role::Prospector, Role::PersonalData], false];
        yield 'a prospector without the right' => ['prospector-without-the-right', [Role::User, Role::Prospector], true];
        yield 'an administrator' => ['administrator', [Role::User, Role::Prospector, Role::Admin], false];
    }

    public function testAMemberWhoIsNoProspectorIsRefused(): void
    {
        $this->login($this->aMemberOfTheFixtures('member', Role::User));

        $this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheSearchRequiresAnAccessToken(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');

        $this->requestJson('GET', '/api/tagged-contacts?tags=CAMPAIGN_149');

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @param array<string, string> $contactDetails
     */
    private function aContact(string $firstName, string $lastName, array $contactDetails): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'contactDetails' => $contactDetails, 'organizationIds' => [$this->organization]])['id'];
    }
}
