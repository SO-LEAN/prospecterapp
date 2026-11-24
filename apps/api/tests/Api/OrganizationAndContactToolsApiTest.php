<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\InMemoryStorage;
use Tests\App\Double\TheWeb;

/**
 * An AI manages the organizations and the contacts as its member does on the screens (ADR 0163).
 */
#[Group('api')]
class OrganizationAndContactToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const string LOGO_ON_THE_WEB = TheWeb::HOST.'/logo.png';

    public function testTheAiChangesTheJobTitleOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('set-contact-job-title', ['id' => $ada, 'jobTitle' => 'CTO']);

        $this->assertSame('CTO', $changed['jobTitle']);
    }

    public function testTheAiRenamesAContact(): void
    {
        $ada = $this->ada();
        $name = ['firstName' => 'Augusta Ada', 'lastName' => 'King'];

        $renamed = $this->assertTheToolActsAsItsEndpoint('set-contact-name', ['id' => $ada, ...$name]);

        $this->assertSame($name, $renamed['name']);
    }

    public function testTheAiChangesTheCivilityOfAContact(): void
    {
        $ada = $this->ada();

        $changed = $this->assertTheToolActsAsItsEndpoint('set-contact-title', ['id' => $ada, 'title' => 'ms']);

        $this->assertSame('ms', $changed['title']);
    }

    public function testTheAiChangesTheContactDetailsOfAContactAndTheirProfileOnLinkedIn(): void
    {
        $ada = $this->ada();
        $details = ['email' => 'ada@lovelace.test', 'phoneNumber' => '03 88 00 00 43', 'language' => 'GB', 'links' => ['linkedin.com/in/ada-lovelace']];

        $changed = $this->assertTheToolActsAsItsEndpoint('update-contact-details', ['id' => $ada, ...$details]);

        $this->assertSame('ada@lovelace.test', self::valueAt($changed, 'contactDetails', 'email'));
        $this->assertSame(['linkedInProfile', 'ada-lovelace'], [self::valueAt($changed, 'contactDetails', 'links', 0, 'source'), self::valueAt($changed, 'contactDetails', 'links', 0, 'identifier')]);
    }

    public function testTheAiCollectsAContactOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $charles = ['firstName' => 'Charles', 'lastName' => 'Babbage', 'title' => 'mr', 'jobTitle' => 'Founder', 'phoneNumber' => '03 88 00 00 44', 'links' => ['linkedin.com/in/charles-babbage'], 'organizationIds' => [$babbage]];

        $collected = $this->assertTheToolActsAsItsEndpoint('create-contact', $charles);

        $this->assertSame([['firstName' => 'Charles', 'lastName' => 'Babbage'], 'Founder'], [$collected['name'], $collected['jobTitle']]);
    }

    public function testTheAiRelocatesAnOrganization(): void
    {
        $babbage = $this->babbage();
        $address = ['street' => '1 place Kléber', 'postalCode' => '67000', 'city' => 'Strasbourg', 'country' => 'FR'];

        $relocated = $this->assertTheToolActsAsItsEndpoint('set-organization-address', ['id' => $babbage, ...$address]);

        $this->assertSame($address, $relocated['address']);
    }

    public function testTheAiWritesTheObservationsOnAnOrganization(): void
    {
        $babbage = $this->babbage();

        $written = $this->assertTheToolActsAsItsEndpoint('set-organization-notes', ['id' => $babbage, 'observations' => 'Rappeler en janvier']);

        $this->assertSame('Rappeler en janvier', $written['observations']);
    }

    public function testTheAiRevisesTheLegalIdentityOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $identity = ['corporateName' => 'Babbage & Sons', 'form' => 'SAS', 'type' => 'Client', 'siret' => '55210055400021'];

        $revised = $this->assertTheToolActsAsItsEndpoint('update-organization-identity', ['id' => $babbage, ...$identity]);

        $this->assertSame($identity, $revised['legalIdentity']);
    }

    public function testTheAiChangesTheContactDetailsOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $details = ['email' => 'hello@babbage.test', 'phoneNumber' => '03 88 00 00 45', 'language' => 'FR', 'links' => ['https://www.welcometothejungle.com/fr/companies/babbage'], 'website' => 'www.babbage.test'];

        $changed = $this->assertTheToolActsAsItsEndpoint('update-organization-details', ['id' => $babbage, ...$details]);

        $this->assertSame(['hello@babbage.test', 'https://www.babbage.test'], [self::valueAt($changed, 'contactDetails', 'email'), self::valueAt($changed, 'contactDetails', 'website')]);
    }

    public function testTheAiAttachesAnOrganizationToItsHolding(): void
    {
        $babbage = $this->babbage();
        $holding = $this->aHolding();

        $attached = $this->assertTheToolActsAsItsEndpoint('attach-to-holding', ['id' => $babbage, 'holdingId' => $holding]);

        $this->assertSame($holding, $attached['holdingId']);
    }

    public function testTheAiDetachesAnOrganizationFromItsHolding(): void
    {
        $babbage = $this->babbage();
        $this->requestJson('PATCH', '/api/organizations/'.$babbage.'/holding', ['holdingId' => $this->aHolding()]);

        $detached = $this->assertTheToolActsAsItsEndpoint('detach-from-holding', ['id' => $babbage]);

        $this->assertNull($detached['holdingId']);
    }

    public function testTheAiRemovesTheLogoOfAnOrganization(): void
    {
        $babbage = $this->babbage();
        $this->uploadImage('/api/organizations/'.$babbage.'/logo');

        $removed = $this->assertTheToolActsAsItsEndpoint('delete-organization-logo', ['id' => $babbage]);

        $this->assertNull($removed['logo']);
    }

    public function testTheAiSaysAPossibleDuplicateIsNone(): void
    {
        $this->login();
        $acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME SAS'], 'address' => ['city' => 'Paris']]), 'id');
        $duplicate = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME s.a.s'], 'address' => ['city' => 'Paris']]), 'id');

        $dismissed = $this->assertTheToolActsAsItsEndpoint('dismiss-possible-duplicate-organization', ['id' => $duplicate, 'duplicateId' => $acme]);

        $this->assertSame([], $dismissed['possibleDuplicates']);
    }

    public function testTheAiSaysAPossibleDuplicateOfAContactIsNone(): void
    {
        $ada = $this->ada();
        $duplicate = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'ada', 'lastName' => 'LOVELACE'], 'organizationIds' => [$this->babbage()]]), 'id');

        $dismissed = $this->assertTheToolActsAsItsEndpoint('dismiss-possible-duplicate-contact', ['id' => $duplicate, 'duplicateId' => $ada]);

        $this->assertSame([], $dismissed['possibleDuplicates']);
    }

    public function testTheAiAddsAContactToAnOrganization(): void
    {
        $babbage = $this->babbage();
        $charles = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Charles', 'lastName' => 'Babbage'], 'organizationIds' => [$this->aHolding()]]), 'id');

        $added = $this->assertTheToolActsAsItsEndpoint('add-organization-contact', ['id' => $babbage, 'personId' => $charles]);

        $this->assertContains($charles, self::arrayAt($added, 'contactIds'));
    }

    public function testTheAiRemovesAContactFromAnOrganization(): void
    {
        $babbage = $this->babbage();
        $charles = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Charles', 'lastName' => 'Babbage'], 'organizationIds' => [$babbage, $this->aHolding()]]), 'id');

        $removed = $this->assertTheToolActsAsItsEndpoint('remove-organization-contact', ['id' => $babbage, 'personId' => $charles]);

        $this->assertNotContains($charles, self::arrayAt($removed, 'contactIds'));
    }

    public function testTheAiStartsProspectingAnOrganization(): void
    {
        $holding = $this->aHolding();
        $acme = ['corporateName' => 'ACME', 'form' => 'SAS', 'type' => 'Prospect', 'phoneNumber' => '01 23 45 67 89', 'language' => 'FR', 'street' => '1 rue de Rivoli', 'postalCode' => '75001', 'city' => 'Paris', 'country' => 'FR', 'observations' => 'Vu au salon', 'holdingId' => $holding];

        $started = $this->assertTheToolActsAsItsEndpoint('start-prospecting', $acme);

        $this->assertSame(['ACME SAS', 'Paris', 'Vu au salon', $holding], [$started['name'], self::valueAt($started, 'address', 'city'), $started['observations'], $started['holdingId']]);
    }

    public function testTheAiStopsProspectingAnOrganization(): void
    {
        $babbage = $this->babbage();

        $this->assertNotTrue($this->callWithoutHandshake('stop-prospecting', ['id' => $babbage])['isError'] ?? false);

        $this->requestJson('GET', '/api/organizations/'.$babbage);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testTheAiGivesTheLogoOfAnOrganizationByItsAddressOnTheWeb(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::service(TheWeb::class)->publish(self::LOGO_ON_THE_WEB, (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $this->login();

        $changed = $this->answerOf($this->callWithoutHandshake('set-organization-logo', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertStringEndsWith('.png', self::stringAt($changed, 'logo', 'url'));
        $this->assertSame($this->requestJson('GET', '/api/organizations/'.$babbage), $changed);
    }

    public function testTheImageDownloadedIsRemovedOnceTheLogoKept(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::service(TheWeb::class)->publish(self::LOGO_ON_THE_WEB, (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $this->login();

        $changed = $this->answerOf($this->callWithoutHandshake('set-organization-logo', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertFileDoesNotExist(self::service(InMemoryStorage::class)->pathGivenFor(self::stringAt($changed, 'logo', 'url')));
    }

    public function testAnImageOnAPrivateNetworkIsNeverDownloaded(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::service(TheWeb::class)->publish(TheWeb::ON_A_PRIVATE_NETWORK.'/logo.png', (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('set-organization-logo', ['id' => $babbage, 'url' => TheWeb::ON_A_PRIVATE_NETWORK.'/logo.png']));

        $this->assertSame([422, 'url'], [$refusal['status'], self::valueAt($refusal, 'violations', 0, 'propertyPath')]);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    public function testAnImageOfMoreThanTwoMegabytesIsNotDownloadedFurther(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::service(TheWeb::class)->publish(self::LOGO_ON_THE_WEB, str_repeat('a', 2_000_001));
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('set-organization-logo', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertSame([422, 'url'], [$refusal['status'], self::valueAt($refusal, 'violations', 0, 'propertyPath')]);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    public function testAnImageIsNeverLookedForWhereItsAddressRedirects(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        $images = self::service(TheWeb::class);
        $images->redirect(self::LOGO_ON_THE_WEB, TheWeb::HOST.'/moved.png');
        $images->publish(TheWeb::HOST.'/moved.png', (string) file_get_contents(__DIR__.'/../Fixtures/logo.png'));
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('set-organization-logo', ['id' => $babbage, 'url' => self::LOGO_ON_THE_WEB]));

        $this->assertSame([422, 'url'], [$refusal['status'], self::valueAt($refusal, 'violations', 0, 'propertyPath')]);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    public function testWhatIsNoImageIsRefusedAsFromTheScreen(): void
    {
        $babbage = $this->babbage();
        $this->keepTheKernel();
        self::service(TheWeb::class)->publish(TheWeb::HOST.'/logo.png', 'no image');
        $this->login();

        $refusal = $this->answerOf($this->callWithoutHandshake('set-organization-logo', ['id' => $babbage, 'url' => TheWeb::HOST.'/logo.png']));

        $this->assertSame(422, $refusal['status']);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$babbage)['logo']);
    }

    /**
     * Ada Lovelace, the contact of the demonstration, at Babbage & Co.
     */
    private function ada(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('GET', '/api/contacts?q=Lovelace'), 'items', 0, 'id');
    }

    /**
     * Babbage & Co, the organization of the demonstration.
     */
    private function babbage(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('GET', '/api/organizations?q=Babbage'), 'items', 0, 'id');
    }

    /**
     * Analytical Group, an organization the account prospects, to hold others.
     */
    private function aHolding(): string
    {
        $this->login();

        return self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Analytical Group']]), 'id');
    }
}
