<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An organization is read with its revision. A screen or an AI sends it back with its change: if another member
 * changed the organization since, the change is refused instead of erasing theirs (ADR 0187).
 */
#[Group('api')]
class OrganizationRevisionApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private string $acme;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->acme = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Acme']]), 'id');
    }

    public function testAnOrganizationIsReadWithItsRevision(): void
    {
        $organization = $this->requestJson('GET', '/api/organizations/'.$this->acme);

        $this->assertSame(1, $organization['revision']);
    }

    public function testContactDetailsSavedOnAnOrganizationChangedSinceTheyWereReadAreRefusedAndTheOtherChangeKept(): void
    {
        $read = self::intAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'revision');
        $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details', ['phoneNumber' => '03 88 11 22 33']);

        $problem = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details?revision='.$read, ['phoneNumber' => '03 88 99 99 99']);

        $this->assertChangedMeanwhile($problem);
        $this->assertSame('+33 3 88 11 22 33', self::valueAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'contactDetails', 'phoneNumber'));
    }

    public function testContactDetailsSavedOnTheRevisionReadAreSavedAndAnsweredWithTheNextRevision(): void
    {
        $read = self::intAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'revision');

        $organization = $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details?revision='.$read, ['phoneNumber' => '03 88 11 22 33']);

        $this->assertResponseIsSuccessful();
        $this->assertSame('+33 3 88 11 22 33', self::valueAt($organization, 'contactDetails', 'phoneNumber'));
        $this->assertSame($read + 1, self::intAt($organization, 'revision'));
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
        $this->assertSame('Acme', self::valueAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'legalIdentity', 'corporateName'));
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
        $globex = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Globex']]), 'id');
        $ada = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$globex]]), 'id');
        $this->changedSinceRead();

        $organization = $this->requestJson('POST', '/api/organizations/'.$this->acme.'/contacts', ['personId' => $ada]);

        $this->assertResponseIsSuccessful();
        $this->assertSame([$ada], $organization['contactIds']);
    }

    public function testAnAIChangingAnOrganizationChangedSinceItReadItIsToldItChanged(): void
    {
        $read = self::intAt($this->answerOf($this->callWithoutHandshake('get-organization', ['id' => $this->acme])), 'revision');
        $this->requestJson('PATCH', '/api/organizations/'.$this->acme.'/contact-details', ['phoneNumber' => '03 88 11 22 33']);

        $refusal = $this->callWithoutHandshake('update-organization-details', ['id' => $this->acme, 'phoneNumber' => '03 88 99 99 99', 'revision' => $read]);

        $this->assertTrue($refusal['isError'] ?? false);
        $this->assertSame('changedMeanwhile', $this->answerOf($refusal)['reason']);
    }

    /**
     * @param array<string, mixed> $arguments
     */
    #[DataProvider('toolsChangingAnOrganization')]
    public function testEachToolChangingAnOrganizationChangedSinceTheAIReadItIsRefused(string $tool, array $arguments): void
    {
        $read = $this->changedSinceRead();

        $refusal = $this->callWithoutHandshake($tool, ['id' => $this->acme, ...$arguments, 'revision' => $read]);

        $this->assertSame('changedMeanwhile', $this->answerOf($refusal)['reason'] ?? null);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function toolsChangingAnOrganization(): iterable
    {
        yield 'identity' => ['update-organization-identity', ['corporateName' => 'Acme Industries']];
        yield 'address' => ['set-organization-address', ['city' => 'Strasbourg']];
        yield 'notes' => ['set-organization-notes', ['observations' => 'Rappeler lundi']];
    }

    /**
     * @return int the revision read before another member changed the organization
     */
    private function changedSinceRead(): int
    {
        $read = self::intAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'revision');
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
