<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\ExchangeChangedMeanwhile;
use Tests\App\Double\InMemoryPrivateStorage;
use Tests\App\Double\OrganizationChangedMeanwhile;

/**
 * Two writes of an organization at once lose neither: an addition is replayed from what it reads again, a change
 * made on the organization as it was read is refused, its author told (ADR 0181). With the delivery `sync` of the
 * tests, a contact collected is attached within its command, which is replayed; with the delivery `outbox` of the
 * instances, the reaction attaching it is applied again by the worker (ADR 0104).
 */
#[Group('api')]
class ConcurrentWritesApiTest extends ApiTestCase
{
    use ReadsResources;

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

        $this->assertEqualsCanonicalizing([$grace, $ada], self::valueAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'contactIds'));
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

    public function testAFileAttachedWhileTheExchangeIsCorrectedMeanwhileIsRefusedForTheStorageKeptItsUpload(): void
    {
        $exchange = self::stringAt($this->requestJson('POST', '/api/contacts/'.$this->collectedFor($this->acme, 'Ada').'/exchanges', ['channel' => 'email', 'direction' => 'inbound']), 'items', 0, 'id');
        $upload = self::stringAt($this->requestJson('POST', '/api/uploads'), 'upload');
        self::service(InMemoryPrivateStorage::class)->send($upload, 2048, 'application/pdf');
        self::service(ExchangeChangedMeanwhile::class)->correctTextMeanwhile('Rappeler lundi');

        $problem = $this->requestJson('POST', '/api/exchanges/'.$exchange.'/attachments', ['upload' => $upload, 'name' => 'devis.pdf']);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
        $detail = $this->requestJson('GET', '/api/exchanges/'.$exchange);
        $this->assertSame([], $detail['attachments']);
        $this->assertSame('Rappeler lundi', $detail['text']);
    }

    public function testAnAdditionStillChangedMeanwhileAfterThreeReplaysIsRefused(): void
    {
        $ada = $this->collectedFor($this->prospect('Globex'), 'Ada');
        $this->organizationChangedMeanwhile()->attachMeanwhile(...array_map(static fn (): PersonId => new PersonId(self::newIdentity()), range(1, 4)));

        $problem = $this->requestJson('POST', '/api/organizations/'.$this->acme.'/contacts', ['personId' => $ada]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('changedMeanwhile', $problem['reason']);
        $this->assertNotContains($ada, self::arrayAt($this->requestJson('GET', '/api/organizations/'.$this->acme), 'contactIds'));
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
        return self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $corporateName]]), 'id');
    }

    private function collectedFor(string $organization, string $firstName): string
    {
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName], 'organizationIds' => [$organization]]);
        $this->assertResponseIsSuccessful();

        return self::stringAt($contact, 'id');
    }

    private function organizationChangedMeanwhile(): OrganizationChangedMeanwhile
    {
        return self::service(OrganizationChangedMeanwhile::class);
    }
}
