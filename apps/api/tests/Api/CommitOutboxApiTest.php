<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * With the delivery outbox, what a command publishes is written with what it changes, in one item of the outbox
 * of the table of its context: the relay publishes it on the bus once the commit is done (a refused commit writes
 * none, CommitOutboxTest).
 */
#[Group('api')]
class CommitOutboxApiTest extends ApiTestCase
{
    private const string DELIVERY = 'INTEGRATION_EVENTS_DELIVERY';

    private string $organization;
    private string $deliveryOfTheTests;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$this->organization]]);
        $this->deliverThroughTheOutbox();
    }

    protected function tearDown(): void
    {
        $_SERVER[self::DELIVERY] = $_ENV[self::DELIVERY] = $this->deliveryOfTheTests;
        parent::tearDown();
    }

    public function testACommandWritesOneItemOfOutboxCarryingItsDomainAndIntegrationEvents(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $this->assertResponseIsSuccessful();
        $items = $this->outboxOf('app.table.prospecting');
        $this->assertCount(1, $items);
        $this->assertEqualsCanonicalizing([
            'prospecter.prospecting.internal ProspectingStopped.v1',
            'prospecter.prospecting.internal ContactsLeftWithoutOrganization.v1',
            'prospecter.prospecting ContactsLeftWithoutOrganization.v1',
        ], array_map(static fn (array $event): string => $event['source'].' '.$event['detailType'], json_decode($items[0]['events'], true, flags: \JSON_THROW_ON_ERROR)));
        $this->assertSame([], $this->outboxOf('app.table.accounts'));
        $this->assertSame([], $this->outboxOf('app.table.personal_data'));
    }

    public function testTheItemOfOutboxExpiresAWeekAfterItsCommit(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $item = $this->outboxOf('app.table.prospecting')[0];
        $this->assertSame('OUTBOX', $item['SK']);
        $this->assertEqualsWithDelta(new \DateTimeImmutable('+7 days')->getTimestamp(), $item['expiresAt'], 60);
    }

    private function deliverThroughTheOutbox(): void
    {
        $this->deliveryOfTheTests = $_SERVER[self::DELIVERY];
        $_SERVER[self::DELIVERY] = $_ENV[self::DELIVERY] = 'outbox';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function outboxOf(string $table): array
    {
        return self::getContainer()->get($table)->scan('OUTBOX#');
    }
}
