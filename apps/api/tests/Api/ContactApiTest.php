<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The persons my organization prospects: never without an organization (ADR 0042).
 */
#[Group('api')]
class ContactApiTest extends ApiTestCase
{
    use ReadsResources;

    private const array ADA = [
        'name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'],
        'title' => 'ms',
        'jobTitle' => 'Directrice commerciale',
        'contactDetails' => ['email' => 'ada@analytical-engine.test', 'phoneNumber' => '03 88 00 00 42', 'language' => 'FR', 'links' => ['linkedin.com/in/countess-of-lovelace?utm_source=share']],
    ];

    private const array ADA_AGAIN = ['name' => ['firstName' => 'ada', 'lastName' => 'LOVELACE'], 'title' => 'x'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testCollectAContactOfAnOrganization(): void
    {
        $organization = $this->anOrganization('ACME');

        $collected = $this->collect(self::ADA, $organization);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/contacts/'.self::stringAt($collected, 'id'));
        $this->assertSame('Directrice commerciale', $collected['jobTitle']);
        $this->assertSame('ms', $collected['title']);
        $this->assertSame(['self', 'rename', 'changeJobTitle', 'changeTitle', 'changeContactDetails', 'forget', 'exchanges', 'toDos', 'tags'], array_keys(self::recordAt($collected, '_links')));
        $this->assertSame(['href' => '/api/contacts/'.self::stringAt($collected, 'id').'/exchanges', 'method' => 'GET'], self::valueAt($collected, '_links', 'exchanges'));
        $this->assertSame([self::stringAt($collected, 'id')], self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization), 'contactIds'));
        $this->assertSame(['ACME'], array_column(self::recordsAt($this->requestJson('GET', '/api/contacts/'.self::stringAt($collected, 'id').'/organizations'), 'items'), 'name'));
    }

    public function testAContactIsNeverAlone(): void
    {
        $problem = $this->requestJson('POST', '/api/contacts', self::ADA);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['organizationIds'], $this->violatedFields($problem));
    }

    public function testAnUnknownOrganizationIsNotFound(): void
    {
        $this->collect(self::ADA, self::newIdentity());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $id = $this->collectedId(self::ADA, $this->anOrganization('ACME'));

        $this->assertSame('Directrice générale', self::valueAt($this->requestJson('PATCH', '/api/contacts/'.$id.'/job-title', ['jobTitle' => 'Directrice générale']), 'jobTitle'));
        $renamed = $this->requestJson('PATCH', '/api/contacts/'.$id.'/name', ['firstName' => 'Augusta Ada', 'lastName' => 'King']);
        $this->assertSame(['firstName' => 'Augusta Ada', 'lastName' => 'King'], $renamed['name']);
        $this->assertSame('Directrice générale', $renamed['jobTitle']);
    }

    public function testTheCivilityIsNotedChangedAndRemovedAndShownInTheLists(): void
    {
        $organization = $this->anOrganization('ACME');
        $id = $this->collectedId(['name' => ['firstName' => 'Camille', 'lastName' => 'Martin']], $organization);
        $this->assertSame('x', self::valueAt($this->requestJson('GET', '/api/contacts/'.$id), 'title'), 'unknown until noted');

        $this->assertSame('mr', self::valueAt($this->requestJson('PATCH', '/api/contacts/'.$id.'/title', ['title' => 'mr']), 'title'));
        $this->assertSame(['mr'], array_column(self::recordsAt($this->requestJson('GET', '/api/contacts?q=martin'), 'items'), 'title'));
        $this->assertSame(['mr'], array_column(self::recordsAt($this->requestJson('GET', '/api/organizations/'.$organization.'/contacts'), 'items'), 'title'));

        $this->assertSame('x', self::valueAt($this->requestJson('PATCH', '/api/contacts/'.$id.'/title', ['title' => 'x']), 'title'));

        $problem = $this->requestJson('PATCH', '/api/contacts/'.$id.'/title', ['title' => 'docteur']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['title'], $this->violatedFields($problem), 'the field the core names');
    }

    public function testTheLinksAreNotedWithTheContactDetailsEachRecognizedByItsReference(): void
    {
        $organization = $this->anOrganization('ACME');
        $collected = $this->collect(self::ADA, $organization);
        $countess = ['source' => 'linkedInProfile', 'identifier' => 'countess-of-lovelace', 'address' => 'https://linkedin.com/in/countess-of-lovelace?utm_source=share'];
        $this->assertSame([$countess], self::valueAt($collected, 'contactDetails', 'links'));
        $this->assertSame([[$countess]], array_map(static fn (array $contact): mixed => self::valueAt($contact, 'contactDetails', 'links'), self::recordsAt($this->requestJson('GET', '/api/organizations/'.$organization.'/contacts'), 'items')));

        $changed = $this->requestJson('PATCH', '/api/contacts/'.self::stringAt($collected, 'id').'/contact-details', ['email' => 'ada@analytical-engine.test', 'links' => ['https://fr.linkedin.com/in/Augusta-Ada/', 'https://www.malt.fr/profile/adalovelace']]);
        $this->assertSame([['linkedInProfile', 'augusta-ada'], ['malt', 'adalovelace']], array_map(static fn (array $link): array => [$link['source'], $link['identifier']], self::recordsAt($changed, 'contactDetails', 'links')));

        $problem = $this->requestJson('PATCH', '/api/contacts/'.self::stringAt($collected, 'id').'/contact-details', ['links' => ['https://www.linkedin.com/company/acme']]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['links'], 'referenceOfAnotherKind'], [$this->violatedFields($problem), $problem['reason']], 'the field the core names');
    }

    #[Group('core-rule')]
    public function testALinkAnotherContactHoldsIsRefusedNamingThem(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collect(self::ADA, $organization);
        $grace = $this->collect(['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper']], $organization);

        $problem = $this->requestJson('PATCH', '/api/contacts/'.self::stringAt($grace, 'id').'/contact-details', ['links' => ['https://www.linkedin.com/in/countess-of-lovelace']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('referenceAlreadyUsed', $problem['reason']);
        $this->assertSame([['propertyPath' => 'links', 'reason' => 'referenceAlreadyUsed', 'context' => ['holder' => 'Ada Lovelace', 'holderId' => self::stringAt($ada, 'id')]]], self::violationsOf($problem));
    }

    public function testWithoutTheRightNoLinkTellsWhichContactHoldsIt(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->collect(self::ADA, $organization);
        $grace = $this->collect(['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper']], $organization);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $problem = $this->requestJson('PATCH', '/api/contacts/'.self::stringAt($grace, 'id').'/contact-details', ['links' => ['https://www.linkedin.com/in/countess-of-lovelace']]);

        $this->assertResponseStatusCodeSame(403);
        $this->assertStringNotContainsString('Lovelace', (string) json_encode($problem));
    }

    #[Group('core-rule')]
    public function testAContactCollectedWithALinkAnotherContactHoldsIsRefusedNamingThem(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collect(self::ADA, $organization);

        $problem = $this->collect(['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'contactDetails' => ['links' => ['https://fr.linkedin.com/in/Countess-Of-Lovelace']]], $organization);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['propertyPath' => 'links', 'reason' => 'referenceAlreadyUsed', 'context' => ['holder' => 'Ada Lovelace', 'holderId' => self::stringAt($ada, 'id')]]], self::violationsOf($problem));
    }

    public function testTheListShowsTheOrganizationsOfEachContact(): void
    {
        $organization = $this->anOrganization('Navy');
        $this->collect(['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'contactDetails' => ['email' => 'grace@navy.test']], $organization);

        $page = $this->requestJson('GET', '/api/contacts?q=hop');

        $this->assertCount(1, self::recordsAt($page, 'items'));
        $this->assertSame('Grace', self::valueAt($page, 'items', 0, 'name', 'firstName'));
        $this->assertSame([['id' => $organization, 'name' => 'Navy', 'organization' => ['href' => '/api/organizations/'.$organization, 'method' => 'GET']]], self::valueAt($page, 'items', 0, 'organizations'));
        $this->assertSame(['self', 'search', 'collect', 'searchByTags'], array_keys(self::recordAt($page, '_links')));
    }

    #[DataProvider('collectors')]
    public function testTheListOffersToCollectAContactToAMemberWithTheRight(string $reference, Role ...$roles): void
    {
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));

        $links = self::recordAt($this->requestJson('GET', '/api/contacts'), '_links');

        $this->assertSame(['self', 'search', 'collect', 'searchByTags'], array_keys($links));
        $this->assertSame(['href' => '/api/contacts', 'method' => 'POST'], $links['collect']);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function collectors(): iterable
    {
        yield 'a prospector with the right to manage personal data' => ['prospector-with-the-right', Role::User, Role::Prospector, Role::PersonalData];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    public function testWithoutTheRightContactsAreNamedTheirDetailsMaskedAndOfferOnlyTheirTimeline(): void
    {
        $organization = $this->anOrganization('ACME');
        $id = $this->collectedId(self::ADA, $organization);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $contact = $this->requestJson('GET', '/api/contacts/'.$id);
        $this->assertTrue($contact['masked']);
        $this->assertSame(['Ada', 'Lovelace', 'ms'], [self::valueAt($contact, 'name', 'firstName'), self::valueAt($contact, 'name', 'lastName'), $contact['title']]);
        $this->assertSame(['a***@***', '******42', [['source' => '***', 'identifier' => '***', 'address' => '***']], 'D***e'], [self::valueAt($contact, 'contactDetails', 'email'), self::valueAt($contact, 'contactDetails', 'phoneNumber'), self::valueAt($contact, 'contactDetails', 'links'), $contact['jobTitle']]);
        $this->assertSame(['self', 'exchanges', 'tags'], array_keys(self::recordAt($contact, '_links')));
        $this->assertSame(['self', 'search', 'searchByTags'], array_keys(self::recordAt($this->requestJson('GET', '/api/contacts'), '_links')));

        $this->collect(self::ADA, $organization);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('PATCH', '/api/contacts/'.$id.'/title', ['title' => 'mr']);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('DELETE', '/api/organizations/'.$organization);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutTheRightContactsAreSearchedByTheirNameNotTheirEmail(): void
    {
        $ada = $this->collectedId(self::ADA, $this->anOrganization('ACME'));
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertContains($ada, array_column(self::recordsAt($this->requestJson('GET', '/api/contacts?q=lovel'), 'items'), 'id'));
        $this->assertNotContains($ada, array_column(self::recordsAt($this->requestJson('GET', '/api/contacts?q=ada@analytical'), 'items'), 'id'));
    }

    #[Group('core-rule')]
    public function testTwoContactsDoNotShareAnEmail(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->collect(self::ADA, $organization);

        $problem = $this->collect(self::ADA, $organization);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem), 'the field the core names');
        $this->assertCount(1, self::arrayAt($this->requestJson('GET', '/api/organizations/'.$organization), 'contactIds'));
    }

    public function testAttachAnExistingContactToAnotherOrganization(): void
    {
        $contact = $this->collectedId(self::ADA, $this->anOrganization('ACME'));
        $group = $this->anOrganization('Groupe ACME');

        $this->assertSame([$contact], self::valueAt($this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact]), 'contactIds'));
        $contacts = self::recordsAt($this->requestJson('GET', '/api/organizations/'.$group.'/contacts'), 'items');
        $this->assertSame('Lovelace', self::valueAt($contacts, 0, 'name', 'lastName'));
        $this->assertSame(['href' => '/api/organizations/'.$group.'/contacts/'.$contact, 'method' => 'DELETE'], self::valueAt($contacts, 0, '_links', 'removeFromOrganization'));

        $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact]);
        $this->assertResponseStatusCodeSame(409);
    }

    public function testAnOrganizationsContactsAreMaskedForAMemberWithoutTheRightToManagePersonalData(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collectedId(self::ADA, $organization);

        $read = self::recordsAt($this->requestJson('GET', '/api/organizations/'.$organization.'/contacts'), 'items');
        $this->login('assistant-user');
        $masked = self::recordsAt($this->requestJson('GET', '/api/organizations/'.$organization.'/contacts'), 'items');

        $this->assertSame([$contact, false, 'Directrice commerciale', 'ada@analytical-engine.test'], [self::valueAt($read, 0, 'id'), self::valueAt($read, 0, 'masked'), self::valueAt($read, 0, 'jobTitle'), self::valueAt($read, 0, 'contactDetails', 'email')]);
        $this->assertSame([$contact, true, 'D***e', 'a***@***', ['contact']], [self::valueAt($masked, 0, 'id'), self::valueAt($masked, 0, 'masked'), self::valueAt($masked, 0, 'jobTitle'), self::valueAt($masked, 0, 'contactDetails', 'email'), array_keys(self::recordAt($masked[0], '_links'))]);
        $this->assertSame([['+33 3 88 00 00 42', 'FR'], ['******42', 'FR']], array_map(static fn (array $contacts): array => [self::valueAt($contacts, 0, 'contactDetails', 'phoneNumber'), self::valueAt($contacts, 0, 'contactDetails', 'language')], [$read, $masked]));
    }

    public function testRemovedFromAnotherOrganizationTheContactStays(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collectedId(self::ADA, $organization);
        $group = $this->anOrganization('Groupe ACME');
        $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact]);

        $this->assertSame([], self::valueAt($this->requestJson('DELETE', '/api/organizations/'.$organization.'/contacts/'.$contact), 'contactIds'));
        $this->requestJson('GET', '/api/contacts/'.$contact);
        $this->assertResponseIsSuccessful();
    }

    public function testRemovedFromItsLastOrganizationTheContactIsErased(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collectedId(self::ADA, $organization);

        $this->requestJson('DELETE', '/api/organizations/'.$organization.'/contacts/'.$contact);

        $this->requestJson('GET', '/api/contacts/'.$contact);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnErasedContactLeavesItsOrganizations(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->collectedId(self::ADA, $organization);

        $this->requestJson('DELETE', '/api/contacts/'.$contact);
        $this->assertResponseStatusCodeSame(204);

        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization), 'contactIds'));
    }

    public function testStoppingToProspectErasesTheContactsLeftAloneAndDetachesTheSubsidiaries(): void
    {
        $group = $this->anOrganization('Groupe ACME');
        $subsidiary = $this->anOrganization('ACME Alsace');
        $this->requestJson('PATCH', '/api/organizations/'.$subsidiary.'/holding', ['holdingId' => $group]);
        $alone = $this->collectedId(self::ADA, $group);
        $shared = $this->collectedId(['name' => ['firstName' => 'Grace'], 'contactDetails' => ['email' => 'grace@navy.test']], $group);
        $this->requestJson('POST', '/api/organizations/'.$subsidiary.'/contacts', ['personId' => $shared]);

        $this->requestJson('DELETE', '/api/organizations/'.$group);
        $this->assertResponseStatusCodeSame(204);

        $this->requestJson('GET', '/api/organizations/'.$group);
        $this->assertResponseStatusCodeSame(404);
        $this->assertNull(self::valueAt($this->requestJson('GET', '/api/organizations/'.$subsidiary), 'holdingId'));
        $this->requestJson('GET', '/api/contacts/'.$alone);
        $this->assertResponseStatusCodeSame(404);
        $this->requestJson('GET', '/api/contacts/'.$shared);
        $this->assertResponseIsSuccessful();
    }

    public function testAContactResemblingAnotherOfTheirOrganizationNamesTheirPossibleDuplicate(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collectedId(self::ADA, $organization);

        $duplicate = $this->collect(self::ADA_AGAIN, $organization);

        $this->assertSame([[
            'id' => $ada,
            'name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'],
            '_links' => [
                'self' => ['href' => '/api/contacts/'.$ada, 'method' => 'GET'],
                'dismiss' => ['href' => '/api/contacts/'.self::stringAt($duplicate, 'id').'/possible-duplicates/'.$ada, 'method' => 'DELETE'],
            ],
        ]], $duplicate['possibleDuplicates']);
    }

    public function testAMemberWithTheRightSaysAPossibleDuplicateIsNone(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collectedId(self::ADA, $organization);
        $duplicate = $this->collectedId(self::ADA_AGAIN, $organization);

        $dismissed = $this->requestJson('DELETE', '/api/contacts/'.$duplicate.'/possible-duplicates/'.$ada);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $dismissed['possibleDuplicates']);
        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/contacts/'.$ada), 'possibleDuplicates'));
    }

    public function testWithoutTheRightAProspectorSeesNoPossibleDuplicate(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->collect(self::ADA, $organization);
        $duplicate = $this->collectedId(self::ADA_AGAIN, $organization);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $this->assertSame([], self::valueAt($this->requestJson('GET', '/api/contacts/'.$duplicate), 'possibleDuplicates'));
    }

    public function testWithoutTheRightAProspectorDoesNotSayAContactIsNoDuplicate(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collectedId(self::ADA, $organization);
        $duplicate = $this->collectedId(self::ADA_AGAIN, $organization);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $problem = $this->requestJson('DELETE', '/api/contacts/'.$duplicate.'/possible-duplicates/'.$ada);

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame('personalDataManagersOnly', $problem['reason']);
    }

    public function testAContactOfAnotherAccountIsNotFoundToSayItIsNoDuplicate(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collectedId(self::ADA, $organization);
        $duplicate = $this->collectedId(self::ADA_AGAIN, $organization);
        $this->login($this->aMemberOfAnotherAccount('stranger'));

        $this->requestJson('DELETE', '/api/contacts/'.$duplicate.'/possible-duplicates/'.$ada);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testSayingAContactNeverSuspectedIsNoDuplicateIsAConflict(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->collectedId(self::ADA, $organization);
        $grace = $this->collectedId(['name' => ['firstName' => 'Grace', 'lastName' => 'Hopper'], 'title' => 'x'], $organization);

        $problem = $this->requestJson('DELETE', '/api/contacts/'.$grace.'/possible-duplicates/'.$ada);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('notASuspectedDuplicateContact', $problem['reason']);
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return array<string, mixed>
     */
    private function collect(array $contact, string $organization): array
    {
        return $this->requestJson('POST', '/api/contacts', [...$contact, 'organizationIds' => [$organization]]);
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function collectedId(array $contact, string $organization): string
    {
        return self::stringAt($this->collect($contact, $organization), 'id');
    }

    /**
     * The violations of a problem, each by its field, its reason and its context.
     *
     * @param array<string, mixed> $problem
     *
     * @return list<array<string, mixed>>
     */
    private static function violationsOf(array $problem): array
    {
        return array_map(static fn (array $violation): array => array_intersect_key($violation, array_flip(['propertyPath', 'reason', 'context'])), self::recordsAt($problem, 'violations'));
    }

    private function anOrganization(string $name): string
    {
        return self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]]), 'id');
    }
}
