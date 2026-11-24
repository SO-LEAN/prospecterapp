<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\PersonalData\Application\CollectContact\CollectContact;
use Prospecter\Core\PersonalData\Application\CompleteContact\CompleteContact;
use Prospecter\Core\Prospecting\Application\AddContact\AddContact;
use Prospecter\Core\Prospecting\Application\CompleteOrganization\CompleteOrganization;
use Prospecter\Core\Prospecting\Application\StartProspecting\StartProspecting;
use Prospecter\Core\SharedKernel\Application\Exception\UseCaseRefused;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;
use Tests\App\Double\FailingCommands;

/**
 * An AI records in one call a contact and the organization they work for (ADR 0177): Prospecter finds what exists,
 * creates what lacks, completes what is empty without changing what is noted, and attaches the contact.
 */
#[Group('api')]
class RecordContactToolApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const array MARGARET_HAMILTON = ['firstName' => 'Margaret', 'lastName' => 'Hamilton', 'jobTitle' => 'CTO', 'links' => ['https://www.linkedin.com/in/margaret-hamilton']];
    private const array ADA_LOVELACE_HOMONYM = ['firstName' => 'Ada', 'lastName' => 'LOVELACE', 'links' => ['https://www.linkedin.com/in/ada-lovelace-1815']];
    private const array BABBAGE = ['email' => 'contact@babbage.test'];
    private const array GLOBEX = ['name' => 'Globex', 'links' => ['https://www.linkedin.com/company/globex']];

    public function testWhatIsNewIsCreatedAndTheContactAttached(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame(['created', 'created'], [self::valueAt($recorded, 'organization', 'status'), self::valueAt($recorded, 'contact', 'status')]);
        $organization = $this->requestJson('GET', '/api/organizations/'.self::stringAt($recorded, 'organization', 'id'));
        $this->assertSame(['Globex', ['https://www.linkedin.com/company/globex'], [self::valueAt($recorded, 'contact', 'id')]], [$organization['name'], self::addressesOf($organization), $organization['contactIds']]);
        $contact = $this->requestJson('GET', '/api/contacts/'.self::stringAt($recorded, 'contact', 'id'));
        $this->assertSame(['CTO', ['https://www.linkedin.com/in/margaret-hamilton']], [$contact['jobTitle'], self::addressesOf($contact)]);
    }

    public function testAnOrganizationTheAiStartsProspectingIsCreatedByAnAi(): void
    {
        $this->login();

        $globex = $this->answerOf($this->callWithoutHandshake('start-prospecting', ['corporateName' => 'Globex']));

        $this->assertTrue($globex['createdByAnAI'] ?? null);
    }

    public function testAnOrganizationTheAiRecordsWithAContactIsCreatedByAnAi(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertTrue($this->requestJson('GET', '/api/organizations/'.self::stringAt($recorded, 'organization', 'id'))['createdByAnAI']);
    }

    public function testAContactTheAiRecordsIsCreatedByAnAi(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertTrue($this->requestJson('GET', '/api/contacts/'.self::stringAt($recorded, 'contact', 'id'))['createdByAnAI']);
    }

    public function testAContactTheAiOnlyCompletesIsNotCreatedByAnAi(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'email' => 'ada@babbage.test', 'jobTitle' => 'CTO'], 'organization' => self::BABBAGE]));

        $this->assertSame(['known', false], [self::valueAt($recorded, 'contact', 'status'), $this->requestJson('GET', '/api/contacts/'.self::stringAt($recorded, 'contact', 'id'))['createdByAnAI']]);
    }

    public function testAContactTheAiCollectsIsCreatedByAnAi(): void
    {
        $this->login();
        $globex = $this->answerOf($this->callWithoutHandshake('start-prospecting', ['corporateName' => 'Globex']))['id'];

        $collected = $this->answerOf($this->callWithoutHandshake('create-contact', ['firstName' => 'Margaret', 'lastName' => 'Hamilton', 'organizationIds' => [$globex]]));

        $this->assertTrue($collected['createdByAnAI'] ?? null);
    }

    public function testAnOrganizationFoundByItsPageIsNotCreatedAgain(): void
    {
        $this->login();
        $globex = self::stringAt($this->callWithoutHandshake('start-prospecting', ['corporateName' => 'Globex', 'links' => ['https://www.linkedin.com/company/globex']]), 'structuredContent', 'id');

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['firstName' => 'Margaret', 'lastName' => 'Hamilton'], 'organization' => ['links' => ['https://www.linkedin.com/company/globex/']]]));

        $this->assertSame(['known', $globex, 'created'], [self::valueAt($recorded, 'organization', 'status'), self::valueAt($recorded, 'organization', 'id'), self::valueAt($recorded, 'contact', 'status')]);
        $this->assertSame([self::valueAt($recorded, 'contact', 'id')], $this->contactIdsOf($globex));
    }

    public function testAContactFoundIsCompletedWithoutChangingWhatIsNoted(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', [
            'person' => ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'email' => 'ada@babbage.test', 'jobTitle' => 'CTO', 'links' => ['https://www.linkedin.com/in/ada-lovelace', 'https://www.malt.fr/profile/adalovelace']],
            'organization' => ['email' => 'contact@babbage.test'],
        ]));

        $contact = self::recordAt($recorded, 'contact');
        $this->assertSame(['known', ['links'], ['jobTitle']], [$contact['status'], $contact['completed'], $contact['differing']]);
        $read = $this->requestJson('GET', '/api/contacts/'.self::stringAt($contact, 'id'));
        $this->assertSame(['Directrice commerciale', ['https://www.linkedin.com/in/ada-lovelace', 'https://www.malt.fr/profile/adalovelace']], [$read['jobTitle'], self::addressesOf($read)]);
    }

    public function testTheSameCallTwiceCreatesNothingMore(): void
    {
        $this->login();
        $first = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $second = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $firstContact = self::recordAt($first, 'contact');
        $secondContact = self::recordAt($second, 'contact');
        $this->assertSame(
            [['known', self::valueAt($first, 'organization', 'id')], ['known', $firstContact['id'], [], []]],
            [[self::valueAt($second, 'organization', 'status'), self::valueAt($second, 'organization', 'id')], [$secondContact['status'], $secondContact['id'], $secondContact['completed'], $secondContact['differing']]],
        );
        $this->assertSame([$firstContact['id']], $this->contactIdsOf(self::stringAt($first, 'organization', 'id')));
    }

    public function testAContactOfAnotherOrganizationStaysThere(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@babbage.test'], 'organization' => self::GLOBEX]));

        $this->assertEqualsCanonicalizing(['Babbage & Co SAS', 'Globex'], $this->organizationNamesOf(self::stringAt($recorded, 'contact', 'id')));
    }

    public function testAPersonToConfirmIsNotCreatedWithoutConfirmation(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE]));

        $contact = self::recordAt($recorded, 'contact');
        $this->assertSame(['toConfirm', null, ['Ada Lovelace']], [$contact['status'], $contact['id'] ?? null, array_column(self::recordsAt($contact, 'candidates'), 'name')]);
        $this->assertSame('new', $this->statusOfThePersonIdentified(['links' => self::ADA_LOVELACE_HOMONYM['links']]));
    }

    public function testTheCandidateRetainedIsCompleted(): void
    {
        $this->login();
        $candidate = $this->theCandidateOfTheHomonym();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE, 'personId' => $candidate]));

        $contact = self::recordAt($recorded, 'contact');
        $this->assertSame(['known', $candidate, ['links']], [$contact['status'], $contact['id'], $contact['completed']]);
    }

    public function testAnotherPersonThanThoseToConfirmIsCreated(): void
    {
        $this->login();
        $candidate = $this->theCandidateOfTheHomonym();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE, 'newPerson' => true]));

        $this->assertSame('created', self::valueAt($recorded, 'contact', 'status'));
        $this->assertNotSame($candidate, self::valueAt($recorded, 'contact', 'id'));
        $this->assertContains(self::valueAt($recorded, 'contact', 'id'), $this->contactIdsOf(self::stringAt($recorded, 'organization', 'id')));
    }

    public function testAnOrganizationToConfirmIsRetainedByItsId(): void
    {
        $this->login();
        $toConfirm = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => ['name' => 'Babbage & Co']]));
        $this->assertSame(['toConfirm', ['Babbage & Co SAS'], 'new'], [self::valueAt($toConfirm, 'organization', 'status'), array_column(self::recordsAt($toConfirm, 'organization', 'candidates'), 'name'), self::valueAt($toConfirm, 'contact', 'status')]);
        $candidate = self::stringAt($toConfirm, 'organization', 'candidates', 0, 'id');

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => ['name' => 'Babbage & Co'], 'organizationId' => $candidate]));

        $this->assertSame(['known', 'created'], [self::valueAt($recorded, 'organization', 'status'), self::valueAt($recorded, 'contact', 'status')]);
        $this->assertContains(self::valueAt($recorded, 'contact', 'id'), $this->contactIdsOf($candidate));
    }

    public function testAnAssistantWithoutTheRightToManageThePersonalDataIsRefusedAsOnTheScreen(): void
    {
        $this->login('assistant-user');

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame([403, 'roleNotAllowed'], [$refused['status'], $refused['reason']]);
        $this->assertSame('new', $this->statusOfTheOrganizationIdentified(self::GLOBEX));
    }

    public function testAMemberWhoIsNoProspectorIsRefused(): void
    {
        $this->aMemberOfTheFixtures('member-user', Role::User, Role::PersonalData);
        $this->login('member-user');

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame([403, 'roleNotAllowed'], [$refused['status'], $refused['reason']]);
    }

    public function testAnOrganizationOfAnotherAccountIsUnknown(): void
    {
        $this->login();
        $babbage = self::stringAt($this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [['emails' => ['contact@babbage.test']]]])), 'organizations', 0, 'candidates', 0, 'id');
        $this->login($this->aMemberOfAnotherAccount('another-user'));

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@another.test'], 'organization' => ['siret' => '55210055400013'], 'organizationId' => $babbage]));

        $this->assertSame([404, 'unknownOrganization'], [$refused['status'], $refused['reason']]);
        $this->login();
        $this->assertNull($this->siretOf($babbage));
    }

    public function testAContactOfAnotherAccountIsUnknown(): void
    {
        $this->login();
        $ada = $this->adaOfBabbage();
        $this->login($this->aMemberOfAnotherAccount('another-user'));

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@babbage.test'], 'organization' => self::GLOBEX, 'personId' => $ada]));

        $this->assertSame([404, 'unknownPerson'], [$refused['status'], $refused['reason']]);
        $this->assertSame('new', $this->statusOfTheOrganizationIdentified(self::GLOBEX));
    }

    public function testAnOrganizationFoundIsCompletedWithoutChangingWhatIsNoted(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', [
            'person' => self::MARGARET_HAMILTON,
            'organization' => ['email' => 'contact@babbage.test', 'name' => 'Babbage & Co', 'siret' => '55210055400013', 'phoneNumber' => '03 88 11 22 33'],
        ]));

        $found = self::recordAt($recorded, 'organization');
        $this->assertSame(['known', ['siret', 'phoneNumber'], []], [$found['status'], $found['completed'], $found['differing']]);
        $organization = $this->requestJson('GET', '/api/organizations/'.self::stringAt($found, 'id'));
        $this->assertSame(['55210055400013', '+33 3 88 11 22 33'], [self::valueAt($organization, 'legalIdentity', 'siret'), self::valueAt($organization, 'contactDetails', 'phoneNumber')]);
    }

    public function testAnOrganizationCreatedKeepsItsSiret(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX + ['siret' => '35600000049837']]));

        $this->assertSame('35600000049837', $this->siretOf(self::stringAt($recorded, 'organization', 'id')));
    }

    public function testAFailedCollectionUndoesTheOrganizationTheCallCreated(): void
    {
        $this->theNextFails(CollectContact::class);

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame(422, $refused['status']);
        $this->assertNothingRemainsOfGlobexNorMargaret();
    }

    public function testAFailedStartCreatesNothing(): void
    {
        $this->theNextFails(StartProspecting::class);

        $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertNothingRemainsOfGlobexNorMargaret();
    }

    public function testAFailedSiretUndoesTheOrganizationTheCallCreated(): void
    {
        $this->theNextFails(CompleteOrganization::class);

        $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX + ['siret' => '35600000049837']]));

        $this->assertNothingRemainsOfGlobexNorMargaret();
    }

    public function testAFailedCompletionOfAnOrganizationKnownLeavesItAsItWas(): void
    {
        $this->theNextFails(CompleteOrganization::class);

        $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::BABBAGE + ['siret' => '55210055400013']]));

        $babbage = self::recordAt($this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [['emails' => ['contact@babbage.test']]]])), 'organizations', 0);
        $this->assertSame('known', $babbage['status']);
        $this->assertNull($this->siretOf(self::stringAt($babbage, 'candidates', 0, 'id')));
        $this->assertNothingRemainsOfGlobexNorMargaret();
    }

    public function testAFailedCompletionOfTheContactUndoesTheOrganizationTheCallCreated(): void
    {
        $this->theNextFails(CompleteContact::class);

        $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@babbage.test', 'links' => ['https://www.malt.fr/profile/adalovelace']], 'organization' => self::GLOBEX]));

        $this->assertNothingRemainsOfGlobexNorMargaret();
        $this->assertAdaIsAsShewas();
    }

    public function testAFailedAttachmentUndoesTheOrganizationTheCallCreated(): void
    {
        $this->theNextFails(AddContact::class);

        $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@babbage.test'], 'organization' => self::GLOBEX]));

        $this->assertNothingRemainsOfGlobexNorMargaret();
        $this->assertAdaIsAsShewas();
    }

    public function testAFailureOfTheServerUndoesTheOrganizationTheCallCreatedToo(): void
    {
        $this->theNextFails(CollectContact::class, new \RuntimeException('DynamoDB out of reach, as the test asks'));

        $this->sendWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]);

        $this->assertNothingRemainsOfGlobexNorMargaret();
    }

    public function testAPersonGivenBadlyCreatesNoOrganization(): void
    {
        $this->theNextFails(StartProspecting::class, new \RuntimeException('Started although the person was given badly'));

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON + ['email' => 'not-an-email'], 'organization' => self::GLOBEX]));

        $this->assertSame([422, ['contactDetails.email']], [$refused['status'], $this->violatedFields($refused)]);
    }

    /**
     * The command of this class fails as its use case would refuse it, or as the server may, the kernel kept for the
     * double to.
     *
     * @param class-string $command
     */
    private function theNextFails(string $command, ?\Throwable $failure = null): void
    {
        $this->keepTheKernel();
        $this->login();
        self::service(FailingCommands::class)->failTheNext($command, $failure ?? UseCaseRefused::because(BusinessRuleViolation::because('Refused as the test asks')));
    }

    private function assertNothingRemainsOfGlobexNorMargaret(): void
    {
        $identified = $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [self::GLOBEX], 'persons' => [['links' => self::MARGARET_HAMILTON['links']]]]));
        $this->assertSame(['new', 'new'], [self::valueAt($identified, 'organizations', 0, 'status'), self::valueAt($identified, 'persons', 0, 'status')]);
    }

    private function assertAdaIsAsShewas(): void
    {
        $ada = $this->adaOfBabbage();
        $this->assertSame(['https://www.linkedin.com/in/ada-lovelace'], self::addressesOf($this->requestJson('GET', '/api/contacts/'.$ada)));
        $this->assertSame(['Babbage & Co SAS'], $this->organizationNamesOf($ada));
    }

    private function adaOfBabbage(): string
    {
        return self::stringAt($this->answerOf($this->callWithoutHandshake('identify', ['persons' => [['emails' => ['ada@babbage.test']]]])), 'persons', 0, 'candidates', 0, 'id');
    }

    private function theCandidateOfTheHomonym(): string
    {
        return self::stringAt($this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE])), 'contact', 'candidates', 0, 'id');
    }

    /**
     * @param array<string, mixed> $person
     */
    private function statusOfThePersonIdentified(array $person): mixed
    {
        return self::valueAt($this->answerOf($this->callWithoutHandshake('identify', ['persons' => [$person]])), 'persons', 0, 'status');
    }

    /**
     * @param array<string, mixed> $organization
     */
    private function statusOfTheOrganizationIdentified(array $organization): mixed
    {
        return self::valueAt($this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [$organization]])), 'organizations', 0, 'status');
    }

    private function siretOf(string $organization): mixed
    {
        return self::valueAt($this->requestJson('GET', '/api/organizations/'.$organization), 'legalIdentity', 'siret');
    }

    /**
     * @return array<mixed>
     */
    private function contactIdsOf(string $organization): array
    {
        return self::arrayAt($this->requestJson('GET', '/api/organizations/'.$organization), 'contactIds');
    }

    /**
     * @return list<mixed>
     */
    private function organizationNamesOf(string $contact): array
    {
        return array_column(self::recordsAt($this->requestJson('GET', '/api/contacts/'.$contact.'/organizations'), 'items'), 'name');
    }

    /**
     * The addresses of the links of an organization or a contact.
     *
     * @param array<string, mixed> $resource
     *
     * @return list<mixed>
     */
    private static function addressesOf(array $resource): array
    {
        return array_column(self::recordsAt($resource, 'contactDetails', 'links'), 'address');
    }
}
