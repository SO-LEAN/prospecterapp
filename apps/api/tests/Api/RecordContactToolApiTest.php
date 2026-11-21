<?php

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

        $this->assertSame(['created', 'created'], [$recorded['organization']['status'], $recorded['contact']['status']]);
        $organization = $this->requestJson('GET', '/api/organizations/'.$recorded['organization']['id']);
        $this->assertSame(['Globex', ['https://www.linkedin.com/company/globex'], [$recorded['contact']['id']]], [$organization['name'], array_column($organization['contactDetails']['links'], 'address'), $organization['contactIds']]);
        $contact = $this->requestJson('GET', '/api/contacts/'.$recorded['contact']['id']);
        $this->assertSame(['CTO', ['https://www.linkedin.com/in/margaret-hamilton']], [$contact['jobTitle'], array_column($contact['contactDetails']['links'], 'address')]);
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

        $this->assertTrue($this->requestJson('GET', '/api/organizations/'.$recorded['organization']['id'])['createdByAnAI']);
    }

    public function testAContactTheAiRecordsIsCreatedByAnAi(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertTrue($this->requestJson('GET', '/api/contacts/'.$recorded['contact']['id'])['createdByAnAI']);
    }

    public function testAContactTheAiOnlyCompletesIsNotCreatedByAnAi(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'email' => 'ada@babbage.test', 'jobTitle' => 'CTO'], 'organization' => self::BABBAGE]));

        $this->assertSame(['known', false], [$recorded['contact']['status'], $this->requestJson('GET', '/api/contacts/'.$recorded['contact']['id'])['createdByAnAI']]);
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
        $globex = $this->callWithoutHandshake('start-prospecting', ['corporateName' => 'Globex', 'links' => ['https://www.linkedin.com/company/globex']])['structuredContent']['id'];

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['firstName' => 'Margaret', 'lastName' => 'Hamilton'], 'organization' => ['links' => ['https://www.linkedin.com/company/globex/']]]));

        $this->assertSame(['known', $globex, 'created'], [$recorded['organization']['status'], $recorded['organization']['id'], $recorded['contact']['status']]);
        $this->assertSame([$recorded['contact']['id']], $this->requestJson('GET', '/api/organizations/'.$globex)['contactIds']);
    }

    public function testAContactFoundIsCompletedWithoutChangingWhatIsNoted(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', [
            'person' => ['firstName' => 'Ada', 'lastName' => 'Lovelace', 'email' => 'ada@babbage.test', 'jobTitle' => 'CTO', 'links' => ['https://www.linkedin.com/in/ada-lovelace', 'https://www.malt.fr/profile/adalovelace']],
            'organization' => ['email' => 'contact@babbage.test'],
        ]));

        $this->assertSame(['known', ['links'], ['jobTitle']], [$recorded['contact']['status'], $recorded['contact']['completed'], $recorded['contact']['differing']]);
        $contact = $this->requestJson('GET', '/api/contacts/'.$recorded['contact']['id']);
        $this->assertSame(['Directrice commerciale', ['https://www.linkedin.com/in/ada-lovelace', 'https://www.malt.fr/profile/adalovelace']], [$contact['jobTitle'], array_column($contact['contactDetails']['links'], 'address')]);
    }

    public function testTheSameCallTwiceCreatesNothingMore(): void
    {
        $this->login();
        $first = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $second = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame(
            [['known', $first['organization']['id']], ['known', $first['contact']['id'], [], []]],
            [[$second['organization']['status'], $second['organization']['id']], [$second['contact']['status'], $second['contact']['id'], $second['contact']['completed'], $second['contact']['differing']]],
        );
        $this->assertSame([$first['contact']['id']], $this->requestJson('GET', '/api/organizations/'.$first['organization']['id'])['contactIds']);
    }

    public function testAContactOfAnotherOrganizationStaysThere(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@babbage.test'], 'organization' => self::GLOBEX]));

        $organizations = $this->requestJson('GET', '/api/contacts/'.$recorded['contact']['id'].'/organizations')['items'];
        $this->assertEqualsCanonicalizing(['Babbage & Co SAS', 'Globex'], array_column($organizations, 'name'));
    }

    public function testAPersonToConfirmIsNotCreatedWithoutConfirmation(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE]));

        $this->assertSame(['toConfirm', null, ['Ada Lovelace']], [$recorded['contact']['status'], $recorded['contact']['id'] ?? null, array_column($recorded['contact']['candidates'], 'name')]);
        $this->assertSame('new', $this->answerOf($this->callWithoutHandshake('identify', ['persons' => [['links' => self::ADA_LOVELACE_HOMONYM['links']]]]))['persons'][0]['status']);
    }

    public function testTheCandidateRetainedIsCompleted(): void
    {
        $this->login();
        $candidate = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE]))['contact']['candidates'][0]['id'];

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE, 'personId' => $candidate]));

        $this->assertSame(['known', $candidate, ['links']], [$recorded['contact']['status'], $recorded['contact']['id'], $recorded['contact']['completed']]);
    }

    public function testAnotherPersonThanThoseToConfirmIsCreated(): void
    {
        $this->login();
        $candidate = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE]))['contact']['candidates'][0]['id'];

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::ADA_LOVELACE_HOMONYM, 'organization' => self::BABBAGE, 'newPerson' => true]));

        $this->assertSame('created', $recorded['contact']['status']);
        $this->assertNotSame($candidate, $recorded['contact']['id']);
        $this->assertContains($recorded['contact']['id'], $this->requestJson('GET', '/api/organizations/'.$recorded['organization']['id'])['contactIds']);
    }

    public function testAnOrganizationToConfirmIsRetainedByItsId(): void
    {
        $this->login();
        $toConfirm = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => ['name' => 'Babbage & Co']]));
        $this->assertSame(['toConfirm', ['Babbage & Co SAS'], 'new'], [$toConfirm['organization']['status'], array_column($toConfirm['organization']['candidates'], 'name'), $toConfirm['contact']['status']]);

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => ['name' => 'Babbage & Co'], 'organizationId' => $toConfirm['organization']['candidates'][0]['id']]));

        $this->assertSame(['known', 'created'], [$recorded['organization']['status'], $recorded['contact']['status']]);
        $this->assertContains($recorded['contact']['id'], $this->requestJson('GET', '/api/organizations/'.$toConfirm['organization']['candidates'][0]['id'])['contactIds']);
    }

    public function testAnAssistantWithoutTheRightToManageThePersonalDataIsRefusedAsOnTheScreen(): void
    {
        $this->login('assistant-user');

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame([403, 'roleNotAllowed'], [$refused['status'], $refused['reason']]);
        $this->assertSame('new', $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [self::GLOBEX]]))['organizations'][0]['status']);
    }

    public function testAMemberWhoIsNoProspectorIsRefused(): void
    {
        $this->aMemberOfTheFixtures('member-user', Role::User, Role::PersonalData);
        $this->login('member-user');

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame([403, 'roleNotAllowed'], [$refused['status'], $refused['reason']]);
    }

    public function testAnAdministratorRecordsAsAProspectorWithTheRight(): void
    {
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->login('admin-user');

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX]));

        $this->assertSame(['created', 'created'], [$recorded['organization']['status'], $recorded['contact']['status']]);
    }

    public function testAnOrganizationOfAnotherAccountIsUnknown(): void
    {
        $this->login();
        $babbage = $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [['emails' => ['contact@babbage.test']]]]))['organizations'][0]['candidates'][0]['id'];
        $this->login($this->aMemberOfAnotherAccount('another-user'));

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@another.test'], 'organization' => ['siret' => '55210055400013'], 'organizationId' => $babbage]));

        $this->assertSame([404, 'unknownOrganization'], [$refused['status'], $refused['reason']]);
        $this->login();
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['legalIdentity']['siret']);
    }

    public function testAContactOfAnotherAccountIsUnknown(): void
    {
        $this->login();
        $ada = $this->answerOf($this->callWithoutHandshake('identify', ['persons' => [['emails' => ['ada@babbage.test']]]]))['persons'][0]['candidates'][0]['id'];
        $this->login($this->aMemberOfAnotherAccount('another-user'));

        $refused = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => ['email' => 'ada@babbage.test'], 'organization' => self::GLOBEX, 'personId' => $ada]));

        $this->assertSame([404, 'unknownPerson'], [$refused['status'], $refused['reason']]);
        $this->assertSame('new', $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [self::GLOBEX]]))['organizations'][0]['status']);
    }

    public function testAnOrganizationFoundIsCompletedWithoutChangingWhatIsNoted(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', [
            'person' => self::MARGARET_HAMILTON,
            'organization' => ['email' => 'contact@babbage.test', 'name' => 'Babbage & Co', 'siret' => '55210055400013', 'phoneNumber' => '03 88 11 22 33'],
        ]));

        $this->assertSame(['known', ['siret', 'phoneNumber'], []], [$recorded['organization']['status'], $recorded['organization']['completed'], $recorded['organization']['differing']]);
        $organization = $this->requestJson('GET', '/api/organizations/'.$recorded['organization']['id']);
        $this->assertSame(['55210055400013', '+33 3 88 11 22 33'], [$organization['legalIdentity']['siret'], $organization['contactDetails']['phoneNumber']]);
    }

    public function testAnOrganizationCreatedKeepsItsSiret(): void
    {
        $this->login();

        $recorded = $this->answerOf($this->callWithoutHandshake('record-contact', ['person' => self::MARGARET_HAMILTON, 'organization' => self::GLOBEX + ['siret' => '35600000049837']]));

        $this->assertSame('35600000049837', $this->requestJson('GET', '/api/organizations/'.$recorded['organization']['id'])['legalIdentity']['siret']);
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

        $babbage = $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [['emails' => ['contact@babbage.test']]]]))['organizations'][0];
        $this->assertSame('known', $babbage['status']);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage['candidates'][0]['id'])['legalIdentity']['siret']);
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
        self::getContainer()->get(FailingCommands::class)->failTheNext($command, $failure ?? UseCaseRefused::because(BusinessRuleViolation::because('Refused as the test asks')));
    }

    private function assertNothingRemainsOfGlobexNorMargaret(): void
    {
        $identified = $this->answerOf($this->callWithoutHandshake('identify', ['organizations' => [self::GLOBEX], 'persons' => [['links' => self::MARGARET_HAMILTON['links']]]]));
        $this->assertSame(['new', 'new'], [$identified['organizations'][0]['status'], $identified['persons'][0]['status']]);
    }

    private function assertAdaIsAsShewas(): void
    {
        $ada = $this->answerOf($this->callWithoutHandshake('identify', ['persons' => [['emails' => ['ada@babbage.test']]]]))['persons'][0]['candidates'][0]['id'];
        $this->assertSame(['https://www.linkedin.com/in/ada-lovelace'], array_column($this->requestJson('GET', '/api/contacts/'.$ada)['contactDetails']['links'], 'address'));
        $this->assertSame(['Babbage & Co SAS'], array_column($this->requestJson('GET', '/api/contacts/'.$ada.'/organizations')['items'], 'name'));
    }
}
