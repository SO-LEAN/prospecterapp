<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\TheWeb;

/**
 * An AI manages the organizations and the contacts as its member does on the screens (ADR 0163).
 */
#[Group('api')]
class OrganizationAndContactToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const string LOGO_ON_THE_WEB = TheWeb::HOST.'/logo.png';

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiChangesTheJobTitleOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-fonction-contact', ['id' => $ada, 'jobTitle' => 'CTO'], 'PATCH', '/api/contacts/'.$ada.'/job-title', ['jobTitle' => 'CTO'], self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('CTO', $changed['jobTitle']);
    }

    public function testTheAiRenamesAContact(): void
    {
        $ada = $this->ada();
        $name = ['firstName' => 'Augusta Ada', 'lastName' => 'King'];

        $renamed = $this->assertTheToolActsAsItsEndpoint('renommer-contact', ['id' => $ada, ...$name], 'PATCH', '/api/contacts/'.$ada.'/name', $name, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame($name, $renamed['name']);
    }

    public function testTheAiChangesTheCivilityOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-civilite-contact', ['id' => $ada, 'civility' => 'ms'], 'PATCH', '/api/contacts/'.$ada.'/civility', ['civility' => 'ms'], self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('ms', $changed['civility']);
    }

    public function testTheAiChangesTheContactDetailsOfAContactAndTheirProfileOnLinkedIn(): void
    {
        $ada = $this->ada();
        $details = ['email' => 'ada@lovelace.test', 'phoneNumber' => '03 88 00 00 43', 'language' => 'GB', 'links' => ['linkedin.com/in/ada-lovelace']];

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-coordonnees-contact', ['id' => $ada, ...$details], 'PATCH', '/api/contacts/'.$ada.'/contact-details', $details, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame('ada@lovelace.test', $changed['contactDetails']['email']);
        $this->assertSame(['linkedin', 'ada-lovelace'], [$changed['contactDetails']['links'][0]['source'], $changed['contactDetails']['links'][0]['identifier']]);
    }

    public function testTheAiCollectsAContactOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $charles = ['firstName' => 'Charles', 'lastName' => 'Babbage', 'civility' => 'mr', 'jobTitle' => 'Founder', 'phoneNumber' => '03 88 00 00 44', 'links' => ['linkedin.com/in/charles-babbage'], 'organizationIds' => [$babbage]];
        $payload = ['name' => ['firstName' => 'Charles', 'lastName' => 'Babbage'], 'civility' => 'mr', 'jobTitle' => 'Founder', 'contactDetails' => ['phoneNumber' => '03 88 00 00 44', 'links' => ['linkedin.com/in/charles-babbage']], 'organizationIds' => [$babbage]];

        $collected = $this->assertTheToolActsAsItsEndpoint('collecter-contact', $charles, 'POST', '/api/contacts', $payload, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame([['firstName' => 'Charles', 'lastName' => 'Babbage'], 'Founder'], [$collected['name'], $collected['jobTitle']]);
    }

    public function testTheAiRelocatesAnOrganization(): void
    {
        $babbage = $this->babbage();
        $address = ['street' => '1 place Kléber', 'postalCode' => '67000', 'city' => 'Strasbourg', 'country' => 'FR'];

        $relocated = $this->assertTheToolActsAsItsEndpoint('demenager-organisation', ['id' => $babbage, ...$address], 'PATCH', '/api/organizations/'.$babbage.'/address', $address, self::PROSPECTORS);

        $this->assertSame($address, $relocated['address']);
    }

    public function testTheAiWritesTheObservationsOnAnOrganization(): void
    {
        $babbage = $this->babbage();

        $written = $this->assertTheToolActsAsItsEndpoint('ecrire-observations-organisation', ['id' => $babbage, 'observations' => 'Rappeler en janvier'], 'PATCH', '/api/organizations/'.$babbage.'/observations', ['observations' => 'Rappeler en janvier'], self::PROSPECTORS);

        $this->assertSame('Rappeler en janvier', $written['observations']);
    }

    public function testTheAiRevisesTheLegalIdentityOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $identity = ['corporateName' => 'Babbage & Sons', 'form' => 'SAS', 'type' => 'Client', 'siret' => '55210055400021'];

        $revised = $this->assertTheToolActsAsItsEndpoint('reviser-identite-organisation', ['id' => $babbage, ...$identity], 'PATCH', '/api/organizations/'.$babbage.'/legal-identity', $identity, self::PROSPECTORS);

        $this->assertSame($identity, $revised['legalIdentity']);
    }

    public function testTheAiChangesTheContactDetailsOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $details = ['email' => 'hello@babbage.test', 'phoneNumber' => '03 88 00 00 45', 'language' => 'FR', 'links' => ['https://www.welcometothejungle.com/fr/companies/babbage'], 'website' => 'www.babbage.test'];

        $changed = $this->assertTheToolActsAsItsEndpoint('changer-coordonnees-organisation', ['id' => $babbage, ...$details], 'PATCH', '/api/organizations/'.$babbage.'/contact-details', $details, self::PROSPECTORS);

        $this->assertSame(['hello@babbage.test', 'https://www.babbage.test'], [$changed['contactDetails']['email'], $changed['contactDetails']['website']]);
    }

    public function testTheAiAttachesAnOrganizationToItsHolding(): void
    {
        $babbage = $this->babbage();
        $holding = $this->aHolding();

        $attached = $this->assertTheToolActsAsItsEndpoint('rattacher-a-holding', ['id' => $babbage, 'holdingId' => $holding], 'PATCH', '/api/organizations/'.$babbage.'/holding', ['holdingId' => $holding], self::PROSPECTORS);

        $this->assertSame($holding, $attached['holdingId']);
    }

    public function testTheAiDetachesAnOrganizationFromItsHoldingOnceItsMemberConfirmed(): void
    {
        $babbage = $this->babbage();
        $this->requestJson('PATCH', '/api/organizations/'.$babbage.'/holding', ['holdingId' => $this->aHolding()]);

        $detached = $this->assertTheToolActsAsItsEndpoint('detacher-de-holding', ['id' => $babbage], 'DELETE', '/api/organizations/'.$babbage.'/holding', null, self::PROSPECTORS);

        $this->assertNull($detached['holdingId']);
        $this->assertTheToolAsksForAConfirmation('detacher-de-holding');
    }

    public function testTheAiRemovesTheLogoOfAnOrganizationOnceItsMemberConfirmed(): void
    {
        $babbage = $this->babbage();
        $this->uploadImage('/api/organizations/'.$babbage.'/logo');

        $removed = $this->assertTheToolActsAsItsEndpoint('retirer-logo-organisation', ['id' => $babbage], 'DELETE', '/api/organizations/'.$babbage.'/logo', null, self::PROSPECTORS);

        $this->assertNull($removed['logo']);
        $this->assertTheToolAsksForAConfirmation('retirer-logo-organisation');
    }

    public function testTheAiSaysAPossibleDuplicateIsNoneOnceItsMemberConfirmed(): void
    {
        $this->login();
        $acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME SAS'], 'address' => ['city' => 'Paris']])['id'];
        $duplicate = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME s.a.s'], 'address' => ['city' => 'Paris']])['id'];

        $dismissed = $this->assertTheToolActsAsItsEndpoint('ecarter-doublon-possible', ['id' => $duplicate, 'duplicateId' => $acme], 'DELETE', '/api/organizations/'.$duplicate.'/possible-duplicates/'.$acme, null, self::PROSPECTORS);

        $this->assertSame([], $dismissed['possibleDuplicates']);
        $this->assertTheToolAsksForAConfirmation('ecarter-doublon-possible');
    }

    public function testTheAiSaysAPossibleDuplicateOfAContactIsNoneOnceItsMemberConfirmed(): void
    {
        $ada = $this->ada();
        $duplicate = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'ada', 'lastName' => 'LOVELACE'], 'organizationIds' => [$this->babbage()]])['id'];

        $dismissed = $this->assertTheToolActsAsItsEndpoint('ecarter-doublon-possible-contact', ['id' => $duplicate, 'duplicateId' => $ada], 'DELETE', '/api/contacts/'.$duplicate.'/possible-duplicates/'.$ada, null, self::PERSONAL_DATA_MANAGERS);

        $this->assertSame([], $dismissed['possibleDuplicates']);
        $this->assertTheToolAsksForAConfirmation('ecarter-doublon-possible-contact');
    }

    public function testTheAiAddsAContactToAnOrganization(): void
    {
        $babbage = $this->babbage();
        $charles = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Charles', 'lastName' => 'Babbage'], 'organizationIds' => [$this->aHolding()]])['id'];

        $added = $this->assertTheToolActsAsItsEndpoint('ajouter-contact-organisation', ['id' => $babbage, 'personId' => $charles], 'POST', '/api/organizations/'.$babbage.'/contacts', ['personId' => $charles], self::PERSONAL_DATA_MANAGERS);

        $this->assertContains($charles, $added['contactIds']);
    }

    public function testTheAiRemovesAContactFromAnOrganizationOnceItsMemberConfirmed(): void
    {
        $babbage = $this->babbage();
        $charles = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Charles', 'lastName' => 'Babbage'], 'organizationIds' => [$babbage, $this->aHolding()]])['id'];

        $removed = $this->assertTheToolActsAsItsEndpoint('retirer-contact-organisation', ['id' => $babbage, 'personId' => $charles], 'DELETE', '/api/organizations/'.$babbage.'/contacts/'.$charles, null, self::PERSONAL_DATA_MANAGERS);

        $this->assertNotContains($charles, $removed['contactIds']);
        $this->assertTheToolAsksForAConfirmation('retirer-contact-organisation');
    }

    public function testTheAiStartsProspectingAnOrganization(): void
    {
        $holding = $this->aHolding();
        $acme = ['corporateName' => 'ACME', 'form' => 'SAS', 'type' => 'Prospect', 'phoneNumber' => '01 23 45 67 89', 'language' => 'FR', 'street' => '1 rue de Rivoli', 'postalCode' => '75001', 'city' => 'Paris', 'country' => 'FR', 'observations' => 'Vu au salon', 'holdingId' => $holding];
        $payload = [
            'legalIdentity' => ['corporateName' => 'ACME', 'form' => 'SAS', 'type' => 'Prospect'],
            'contactDetails' => ['phoneNumber' => '01 23 45 67 89', 'language' => 'FR'],
            'address' => ['street' => '1 rue de Rivoli', 'postalCode' => '75001', 'city' => 'Paris', 'country' => 'FR'],
            'observations' => 'Vu au salon',
            'holdingId' => $holding,
        ];

        $started = $this->assertTheToolActsAsItsEndpoint('prospecter-organisation', $acme, 'POST', '/api/organizations', $payload, self::PROSPECTORS);

        $this->assertSame(['ACME SAS', 'Paris', 'Vu au salon', $holding], [$started['name'], $started['address']['city'], $started['observations'], $started['holdingId']]);
    }

    public function testTheAiStopsProspectingAnOrganizationOnceItsMemberConfirmed(): void
    {
        $babbage = $this->babbage();
        foreach (['assistant-user', 'member-user', 'stranger'] as $refused) {
            $this->login($refused);
            $refusal = $this->callWithoutHandshake('arreter-prospection', ['id' => $babbage]);

            $this->assertSame($this->requestJson('DELETE', '/api/organizations/'.$babbage), $this->answerOf($refusal), sprintf('refused to %s', $refused));
        }
        $this->login();

        $this->assertNotTrue($this->callWithoutHandshake('arreter-prospection', ['id' => $babbage])['isError'] ?? false);

        $this->requestJson('GET', '/api/organizations/'.$babbage);
        $this->assertResponseStatusCodeSame(404);
        $this->assertTheToolAsksForAConfirmation('arreter-prospection');
    }

    public function testTheAiOfAProspectorWithoutTheRightToManageThePersonalDataStopsProspectingAnOrganizationWithoutContacts(): void
    {
        $holding = $this->aHolding();
        $this->login('assistant-user');

        $this->assertNotTrue($this->callWithoutHandshake('arreter-prospection', ['id' => $holding])['isError'] ?? false);

        $this->requestJson('GET', '/api/organizations/'.$holding);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheAiGivesTheLogoOfAnOrganizationByItsAddressOnTheWeb(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::getContainer()->get(TheWeb::class)->publish(self::LOGO_ON_THE_WEB, (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));

        foreach (['member-user', 'stranger'] as $refused) {
            $this->login($refused);
            $refusal = $this->answerOf($this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

            $this->assertSame($this->uploadImage('/api/organizations/'.$babbage.'/logo'), $refusal, sprintf('refused to %s', $refused));
        }
        $this->login();

        $changed = $this->answerOf($this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertStringEndsWith('.png', $changed['logo']['url']);
        $this->assertSame($this->requestJson('GET', '/api/organizations/'.$babbage), $changed);
    }

    public function testTheImageDownloadedIsRemovedOnceTheLogoKept(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        $logo = (string) file_get_contents(__DIR__.'/../Fixtures/logo.png');
        self::getContainer()->get(TheWeb::class)->publish(self::LOGO_ON_THE_WEB, $logo);
        $this->login();
        $before = self::temporaryFilesHolding($logo);

        $this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]);

        $this->assertSame($before, self::temporaryFilesHolding($logo));
    }

    public function testAnImageOnAPrivateNetworkIsNeverDownloaded(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::getContainer()->get(TheWeb::class)->publish(TheWeb::ON_A_PRIVATE_NETWORK.'/logo.png', (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => TheWeb::ON_A_PRIVATE_NETWORK.'/logo.png']));

        $this->assertSame([422, 'url'], [$refusal['status'], $refusal['violations'][0]['propertyPath']]);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    public function testAnImageOfMoreThanTwoMegabytesIsNotDownloadedFurther(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::getContainer()->get(TheWeb::class)->publish(self::LOGO_ON_THE_WEB, str_repeat('a', 2_000_001));
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertSame([422, 'url'], [$refusal['status'], $refusal['violations'][0]['propertyPath']]);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    public function testAnImageIsNeverLookedForWhereItsAddressRedirects(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        $images = self::getContainer()->get(TheWeb::class);
        $images->redirect(self::LOGO_ON_THE_WEB, TheWeb::HOST.'/moved.png');
        $images->publish(TheWeb::HOST.'/moved.png', (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertSame([422, 'url'], [$refusal['status'], $refusal['violations'][0]['propertyPath']]);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    public function testWhatIsNoImageIsRefusedAsFromTheScreen(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::getContainer()->get(TheWeb::class)->publish(TheWeb::HOST.'/logo.png', 'no image');
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('changer-logo-organisation', ['id' => $babbage, 'url' => TheWeb::HOST.'/logo.png']));

        $this->assertSame(422, $refusal['status']);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/contacts?q=Lovelace')['items'][0]['id'];
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return $this->requestJson('GET', '/api/organizations?q=Babbage')['items'][0]['id'];
    }

    /**
     * @return list<string> the files of the temporary directory holding this content
     */
    private static function temporaryFilesHolding(string $content): array
    {
        return array_values(array_filter(glob(sys_get_temp_dir().'/*') ?: [], static fn (string $file): bool => is_file($file) && filesize($file) === strlen($content) && file_get_contents($file) === $content));
    }

    /**
     * Analytical Group, an organization the account prospects, to hold others.
     */
    private function aHolding(): string
    {
        $this->login();

        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Analytical Group']])['id'];
    }
}
