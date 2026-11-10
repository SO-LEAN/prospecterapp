<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\DeliversThroughTheOutbox;

/**
 * With the delivery outbox, what a command publishes is written with what it changes, in one item of the outbox
 * of the table of its context: the relay publishes it on the bus once the commit is done (a refused commit writes
 * none, CommitOutboxTest).
 */
#[Group('api')]
class CommitOutboxApiTest extends ApiTestCase
{
    use DeliversThroughTheOutbox;

    private string $organization;
    private string $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$this->organization]])['id'];
        $this->deliverThroughTheOutbox();
    }

    protected function tearDown(): void
    {
        $this->deliverAsTheTestsDo();
        parent::tearDown();
    }

    public function testACommandWritesOneItemOfOutboxCarryingItsDomainAndIntegrationEvents(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $this->assertResponseIsSuccessful();
        $items = $this->outboxOf('prospecting');
        $this->assertCount(1, $items);
        $this->assertEqualsCanonicalizing([
            'prospecter.prospecting.internal ProspectingStopped.v1',
            'prospecter.prospecting.internal ContactsLeftWithoutOrganization.v1',
            'prospecter.prospecting ContactsLeftWithoutOrganization.v1',
            'prospecter.prospecting OrganizationFactsChanged.v1',
            'prospecter.prospecting OrganizationFactsChanged.v1',
        ], array_map(static fn (array $event): string => $event['source'].' '.$event['detailType'], json_decode($items[0]['events'], true, flags: \JSON_THROW_ON_ERROR)));
        $this->assertSame([], $this->outboxOf('accounts'));
        $this->assertSame([], $this->outboxOf('personal_data'));
    }

    public function testAnOrganizationNoLongerProspectedPublishesItsTerminalFacts(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $events = json_decode($this->outboxOf('prospecting')[0]['events'], true, flags: \JSON_THROW_ON_ERROR);
        $facts = array_values(array_filter($events, static fn (array $event): bool => 'OrganizationFactsChanged.v1' === $event['detailType']));
        $this->assertSame(
            ['organizationId' => $this->organization, 'revision' => 0, 'present' => false, 'located' => false],
            array_intersect_key($facts[0]['detail']['payload'], ['organizationId' => true, 'revision' => true, 'present' => true, 'located' => true]),
        );
    }

    public function testANotedExchangePublishesItsFacts(): void
    {
        $this->requestJson('POST', '/api/contacts/'.$this->contact.'/exchanges', ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-10T09:00:00+00:00']);

        $events = json_decode($this->outboxOf('prospecting')[0]['events'], true, flags: \JSON_THROW_ON_ERROR);
        $facts = array_values(array_filter($events, static fn (array $event): bool => 'ExchangeFactsChanged.v1' === $event['detailType']));
        $this->assertCount(1, $facts);
        $this->assertSame(
            ['revision' => 1, 'present' => true, 'notedAt' => '2026-10-10T09:00:00.000000Z'],
            array_intersect_key($facts[0]['detail']['payload'], ['revision' => true, 'present' => true, 'notedAt' => true]),
        );
    }

    public function testTheItemOfOutboxExpiresAWeekAfterItsCommit(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $item = $this->outboxOf('prospecting')[0];
        $this->assertSame('OUTBOX', $item['SK']);
        $this->assertEqualsWithDelta(new \DateTimeImmutable('+7 days')->getTimestamp(), $item['expiresAt'], 60);
    }
}
