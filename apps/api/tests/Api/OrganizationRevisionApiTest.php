<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * An organization is read with its revision. A screen or an AI sends it back with its change: if another member
 * changed the organization since, the change is refused instead of erasing theirs (ADR 0187).
 */
#[Group('api')]
class OrganizationRevisionApiTest extends ApiTestCase
{
    private string $acme;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->acme = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Acme']])['id'];
    }

    public function testAnOrganizationIsReadWithItsRevision(): void
    {
        $organization = $this->requestJson('GET', '/api/organizations/'.$this->acme);

        $this->assertSame(1, $organization['revision']);
    }

    public function testContactDetailsSavedOnAnOrganizationChangedSinceTheyWereReadAreRefusedAndTheOtherChangeKept(): void
    {
        $read = $this->requestJson('GET', '/api/organizations/'.$this->acme)['revision'];
        $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details', ['phoneNumber' => '03 88 11 22 33']);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details?revision='.$read, ['phoneNumber' => '03 88 99 99 99']);

        $this->assertChangedMeanwhile($problem);
        $this->assertSame('+33 3 88 11 22 33', $this->requestJson('GET', '/api/organizations/'.$this->acme)['contactDetails']['phoneNumber']);
    }

    public function testContactDetailsSavedOnTheRevisionReadAreSavedAndAnsweredWithTheNextRevision(): void
    {
        $read = $this->requestJson('GET', '/api/organizations/'.$this->acme)['revision'];

        $organization = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details?revision='.$read, ['phoneNumber' => '03 88 11 22 33']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('+33 3 88 11 22 33', $organization['contactDetails']['phoneNumber']);
        $this->assertSame($read + 1, $organization['revision']);
    }

    public function testARevisionThatIsNoNaturalNumberIsRefused(): void
    {
        $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details?revision=-1', ['phoneNumber' => '03 88 11 22 33']);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testALegalIdentityRevisedOnAnOrganizationChangedSinceItWasReadIsRefused(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/legal-identity?revision='.$read, ['corporateName' => 'Acme Industries']);

        $this->assertChangedMeanwhile($problem);
        $this->assertSame('Acme', $this->requestJson('GET', '/api/organizations/'.$this->acme)['legalIdentity']['corporateName']);
    }

    public function testObservationsWrittenOnAnOrganizationChangedSinceItWasReadAreRefused(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/observations?revision='.$read, ['observations' => 'Rappeler lundi']);

        $this->assertChangedMeanwhile($problem);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$this->acme)['observations']);
    }

    public function testAnOrganizationChangedSinceItWasReadIsNotRelocated(): void
    {
        $read = $this->changedSinceRead();

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/address?revision='.$read, ['city' => 'Strasbourg']);

        $this->assertChangedMeanwhile($problem);
        $this->assertNull($this->requestJson('GET', '/api/organizations/'.$this->acme)['address']);
    }

    public function testAContactAttachedFromAScreenReadBeforeAChangeIsAttached(): void
    {
        $globex = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']])['id'];
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$globex]])['id'];
        $this->changedSinceRead();

        $organization = $this->requestJson('POST', '/api/organizations/'.$this->acme.'/contacts', ['personId' => $ada]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([$ada], $organization['contactIds']);
    }

    /**
     * @return int the revision read before another member changed the organization
     */
    private function changedSinceRead(): int
    {
        $read = $this->requestJson('GET', '/api/organizations/'.$this->acme)['revision'];
        $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details', ['phoneNumber' => '03 88 11 22 33']);

        return $read;
    }

    /**
     * @param array<string, mixed> $problem
     */
    private function assertChangedMeanwhile(array $problem): void
    {
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
    }
}
