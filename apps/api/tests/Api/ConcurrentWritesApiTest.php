<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\OrganizationChangedMeanwhile;

/**
 * Two writes of an organization at once lose neither: an addition is replayed from what it reads again, a change
 * made on the organization as it was read is refused, its author told (ADR 0181).
 */
#[Group('api')]
class ConcurrentWritesApiTest extends ApiTestCase
{
    private string $acme;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->login();
        $this->acme = $this->prospect('Acme');
    }

    public function testTwoContactsCollectedAtOnceAreBothAttached(): void
    {
        $grace = $this->collectedFor($this->prospect('Globex'), 'Grace');
        $this->organizationChangedMeanwhile()->attachMeanwhile(new PersonId($grace));

        $ada = $this->collectedFor($this->acme, 'Ada');

        $this->assertEqualsCanonicalizing([$grace, $ada], $this->requestJson('GET', '/api/organizations/'.$this->acme)['contactIds']);
    }

    public function testAContactAttachedWhileAnotherIsAttachedMeanwhileIsAttachedToo(): void
    {
        $ada = $this->collectedFor($this->prospect('Globex'), 'Ada');
        $grace = self::newIdentity();
        $this->organizationChangedMeanwhile()->attachMeanwhile(new PersonId($grace));

        $organization = $this->requestJson('POST', '/api/organizations/'.$this->acme.'/contacts', ['personId' => $ada]);

        $this->assertResponseIsSuccessful();
        $this->assertEqualsCanonicalizing([$grace, $ada], $organization['contactIds']);
    }

    public function testAnAdditionStillChangedMeanwhileAfterThreeReplaysIsRefused(): void
    {
        $ada = $this->collectedFor($this->prospect('Globex'), 'Ada');
        $this->organizationChangedMeanwhile()->attachMeanwhile(...array_map(static fn (): PersonId => new PersonId(self::newIdentity()), range(1, 4)));

        $problem = $this->requestJson('POST', '/api/organizations/'.$this->acme.'/contacts', ['personId' => $ada]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
        $this->assertNotContains($ada, $this->requestJson('GET', '/api/organizations/'.$this->acme)['contactIds']);
    }

    public function testStoppingToProspectErasesTheContactsCollectedAtOnce(): void
    {
        $globex = $this->prospect('Globex');
        $grace = $this->collectedFor($globex, 'Grace');
        $this->organizationChangedMeanwhile()->attachMeanwhile(new PersonId($grace));
        $ada = $this->collectedFor($this->acme, 'Ada');
        $this->requestJson('DELETE', '/api/organizations/'.$globex);

        $this->requestJson('DELETE', '/api/organizations/'.$this->acme);

        $this->assertResponseStatusCodeSame(204);
        $this->requestJson('GET', '/api/contacts/'.$grace);
        $this->assertResponseStatusCodeSame(404);
        $this->requestJson('GET', '/api/contacts/'.$ada);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAChangeOfAnOrganizationChangedMeanwhileIsRefusedAndTheOtherChangeKept(): void
    {
        $grace = $this->collectedFor($this->prospect('Globex'), 'Grace');
        $this->organizationChangedMeanwhile()->attachMeanwhile(new PersonId($grace));

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/observations', ['observations' => 'Rappeler lundi']);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
        $organization = $this->requestJson('GET', '/api/organizations/'.$this->acme);
        $this->assertSame([$grace], $organization['contactIds']);
        $this->assertNull($organization['observations']);
    }

    private function prospect(string $corporateName): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $corporateName]])['id'];
    }

    private function collectedFor(string $organization, string $firstName): string
    {
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName], 'organizationIds' => [$organization]]);
        $this->assertResponseIsSuccessful();

        return $contact['id'];
    }

    private function organizationChangedMeanwhile(): OrganizationChangedMeanwhile
    {
        return self::getContainer()->get(OrganizationChangedMeanwhile::class);
    }
}
