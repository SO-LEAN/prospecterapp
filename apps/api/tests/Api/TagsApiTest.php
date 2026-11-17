<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\Campaigns\Domain\Model\Holder;
use Prospecter\Core\Campaigns\Domain\Repository\TaggingRepository;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;

/**
 * The tags the members put by hand on their organizations and their contacts, and those proposed to their account
 * (Campaigns, ADR 0138).
 */
#[Group('api')]
class TagsApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAnOrganizationIsTaggedThenATagTakenOff(): void
    {
        $organization = $this->anOrganization('ACME');

        $tagged = $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN', 'apporteur']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['#APPORTEUR', '#ESN'], array_column($tagged['tags'], 'name'));
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/tags/ESN', 'method' => 'DELETE'], $tagged['tags'][1]['_links']['takeOff']);
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/tags', 'method' => 'POST'], $tagged['_links']['tag']);
        $this->assertSame($tagged, $this->requestJson('GET', '/api/organizations/'.$organization.'/tags'));

        $takenOff = $this->requestJson('DELETE', '/api/organizations/'.$organization.'/tags/ESN');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['#APPORTEUR'], array_column($takenOff['tags'], 'name'));
    }

    public function testAContactIsTagged(): void
    {
        $ada = $this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace');

        $tagged = $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#CLIENT']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['#CLIENT'], array_column($tagged['tags'], 'name'));
        $this->assertSame(['href' => '/api/contacts/'.$ada.'/tags/CLIENT', 'method' => 'DELETE'], $tagged['tags'][0]['_links']['takeOff']);
        $this->assertSame($tagged, $this->requestJson('GET', '/api/contacts/'.$ada.'/tags'));
    }

    public function testAContactCarriesItsOwnTagsThoseOfItsOrganizationsAndThoseOfHowItIsReached(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aReachedContactOf($organization);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN', '#PHP']]);

        $tagged = $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#PHP']]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['#PHP'], array_column($tagged['tags'], 'name'));
        $this->assertSame([['name' => '#ESN', 'from' => [$organization]]], $tagged['inherited']);
        $this->assertSame(['#EMAIL', '#LINKEDIN', '#PHONE', '#SMS'], $tagged['automatic']);
        $this->assertSame($tagged, $this->requestJson('GET', '/api/contacts/'.$ada.'/tags'));

        $takenOff = $this->requestJson('DELETE', '/api/contacts/'.$ada.'/tags/PHP');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([], $takenOff['tags']);
        $this->assertSame([['name' => '#ESN', 'from' => [$organization]], ['name' => '#PHP', 'from' => [$organization]]], $takenOff['inherited']);
    }

    public function testTheListOfTheContactsShowsTheTagsEachCarries(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aReachedContactOf($organization);
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN']]);

        $list = $this->requestJson('GET', '/api/contacts?q=Lovelace');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['#EMAIL', '#ESN', '#LINKEDIN', '#PHONE', '#SMS'], array_find($list['items'], static fn (array $contact): bool => $contact['id'] === $ada)['tags'] ?? null);
    }

    public function testTheTagsOfAContactRequireAnAccessToken(): void
    {
        $ada = $this->aContactOf($this->anOrganization('ACME'), 'Ada', 'Lovelace');
        $this->client->setServerParameter('HTTP_AUTHORIZATION', '');

        $this->requestJson('GET', '/api/contacts/'.$ada.'/tags');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testTheCardsLeadToTheirTags(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');

        $this->assertSame(['href' => '/api/organizations/'.$organization.'/tags', 'method' => 'GET'], $this->requestJson('GET', '/api/organizations/'.$organization)['_links']['tags']);
        $this->assertSame(['href' => '/api/contacts/'.$ada.'/tags', 'method' => 'GET'], $this->requestJson('GET', '/api/contacts/'.$ada)['_links']['tags']);
    }

    public function testTheTagsProposedAreThePredefinedOnesThenThoseOfTheAccount(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN', '#SALON_2026']]);

        $proposed = $this->requestJson('GET', '/api/tags');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['organizationType', 'relationship', 'technology'], array_column($proposed['families'], 'family'));
        $this->assertContains('#ESN', $proposed['families'][0]['tags']);
        $this->assertSame(['#SALON_2026'], $proposed['own']);
    }

    #[DataProvider('refusedTags')]
    public function testATagThatCannotBePutByHandIsRefused(string $typed, string $reason): void
    {
        $organization = $this->anOrganization('ACME');

        $problem = $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => [$typed]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['tags'], $reason], [$this->violatedFields($problem), $problem['reason']]);
        $this->assertSame([], $this->requestJson('GET', '/api/organizations/'.$organization.'/tags')['tags']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function refusedTags(): iterable
    {
        yield 'an automatic tag' => ['#EMAIL', 'automaticTag'];
        yield 'no tag at all' => ['#!?', 'invalidTag'];
    }

    public function testNoTagGivenIsRefused(): void
    {
        $organization = $this->anOrganization('ACME');

        $problem = $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => []]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['tags'], $this->violatedFields($problem));
    }

    public function testATagNotCarriedIsNotFound(): void
    {
        $organization = $this->anOrganization('ACME');

        $this->requestJson('DELETE', '/api/organizations/'.$organization.'/tags/ESN');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWhatTheAccountDoesNotKnowIsNotFound(): void
    {
        $this->requestJson('GET', '/api/organizations/'.self::newIdentity().'/tags');
        $this->assertResponseStatusCodeSame(404);

        $problem = $this->requestJson('POST', '/api/contacts/'.self::newIdentity().'/tags', ['tags' => ['#CLIENT']]);
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $problem['reason']);
    }

    public function testTheTagsOfAPersonTheAccountKeepsNotAsAContactAreNotFound(): void
    {
        $unknown = $this->requestJson('GET', '/api/contacts/'.self::newIdentity().'/tags');
        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $unknown['reason']);

        $ofAnotherAccount = Person::collect(new PersonId(self::newIdentity()), new AccountId(self::newIdentity()), Purpose::Prospecting, new \DateTimeImmutable(), new PersonName('Linus', 'Torvalds'));
        self::getContainer()->get(PersonRepository::class)->save($ofAnotherAccount);
        $theirs = $this->requestJson('GET', '/api/contacts/'.$ofAnotherAccount->id()->value.'/tags');

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownContact', $theirs['reason']);
    }

    /**
     * Every prospector tags, with or without the right to manage personal data (a tag is no personal data of PersonalData).
     *
     * @param list<Role> $roles
     */
    #[DataProvider('prospectors')]
    public function testEveryProspectorTagsOrganizationsAndContacts(string $reference, array $roles): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN']]);
        $this->assertResponseStatusCodeSame(200);
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#DECIDEUR']]);
        $this->assertResponseStatusCodeSame(200);
        $this->requestJson('DELETE', '/api/contacts/'.$ada.'/tags/DECIDEUR');
        $this->assertResponseStatusCodeSame(200);
        $this->requestJson('GET', '/api/tags');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['name' => '#ESN', 'from' => [$organization]]], $this->requestJson('GET', '/api/contacts/'.$ada.'/tags')['inherited']);
        $this->assertResponseStatusCodeSame(200);
    }

    /**
     * @return iterable<string, array{string, list<Role>}>
     */
    public static function prospectors(): iterable
    {
        yield 'a prospector with the right' => ['prospector-with-the-right', [Role::User, Role::Prospector, Role::PersonalData]];
        yield 'a prospector without the right' => ['prospector-without-the-right', [Role::User, Role::Prospector]];
        yield 'an administrator' => ['administrator', [Role::User, Role::Prospector, Role::Admin]];
    }

    public function testAMemberWhoIsNoProspectorIsRefused(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->login($this->aMemberOfTheFixtures('member', Role::User));

        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN']]);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('GET', '/api/tags');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnOrganizationNoLongerProspectedTakesItsTagsAndThoseOfItsContactsWithIt(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->requestJson('POST', '/api/organizations/'.$organization.'/tags', ['tags' => ['#ESN']]);
        $this->requestJson('POST', '/api/contacts/'.$ada.'/tags', ['tags' => ['#CLIENT']]);

        $this->requestJson('DELETE', '/api/organizations/'.$organization);
        $this->assertResponseStatusCodeSame(204);

        $taggings = self::getContainer()->get(TaggingRepository::class);
        $this->assertNull($taggings->find($this->accountOfTheFixtures(), Holder::organization(new OrganizationId($organization))));
        $this->assertNull($taggings->find($this->accountOfTheFixtures(), Holder::contact(new PersonId($ada))));
        $this->assertSame([], $this->requestJson('GET', '/api/tags')['own']);
    }

    private function aContactOf(string $organization, string $firstName, string $lastName): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'organizationIds' => [$organization]])['id'];
    }

    private function aReachedContactOf(string $organization): string
    {
        return $this->requestJson('POST', '/api/contacts', [
            'name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'],
            'contactDetails' => ['email' => 'ada@acme.test', 'phoneNumber' => '06 12 34 56 78', 'linkedInProfile' => 'https://www.linkedin.com/in/ada-lovelace'],
            'organizationIds' => [$organization],
        ])['id'];
    }

    private function anOrganization(string $name): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]])['id'];
    }
}
