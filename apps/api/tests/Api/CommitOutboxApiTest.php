<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Shared\Persistence\Documents;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\DeliversThroughTheOutbox;
use Tests\App\Base\ReadsResources;

/**
 * With the delivery outbox, what a command publishes is written with what it changes, in one item of the outbox
 * of the table of its context: the relay publishes it on the bus once the commit is done (a refused commit writes
 * none, CommitOutboxTest).
 */
#[Group('api')]
class CommitOutboxApiTest extends ApiTestCase
{
    use DeliversThroughTheOutbox;
    use ReadsResources;

    private string $organization;
    private string $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
        $this->organization = self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']]), 'id');
        $this->contact = self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$this->organization]]), 'id');
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
            'prospecter.prospecting OrganizationFactsChanged.v3',
        ], array_map(self::nameOf(...), $this->eventsOf('prospecting')));
        $this->assertSame([], $this->outboxOf('accounts'));
        $this->assertSame([], $this->outboxOf('personal_data'));
    }

    public function testAnOrganizationNoLongerProspectedPublishesItsTerminalFacts(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $events = $this->eventsOf('prospecting');
        $facts = array_values(array_filter($events, static fn (array $event): bool => 'OrganizationFactsChanged.v3' === $event['detailType']));
        $this->assertSame(
            ['organizationId' => $this->organization, 'revision' => 0, 'present' => false, 'located' => false, 'contacts' => []],
            array_intersect_key(self::recordAt($facts, 0, 'detail', 'payload'), ['organizationId' => true, 'revision' => true, 'present' => true, 'located' => true, 'contacts' => true]),
        );
    }

    public function testANotedExchangePublishesItsFacts(): void
    {
        $this->requestJson('POST', '/api/contacts/'.$this->contact.'/exchanges', ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-10T09:00:00+00:00']);

        $events = $this->eventsOf('prospecting');
        $facts = array_values(array_filter($events, static fn (array $event): bool => 'ExchangeFactsChanged.v4' === $event['detailType']));
        $this->assertCount(1, $facts);
        $this->assertSame(
            ['revision' => 1, 'present' => true, 'status' => 'done', 'notedAt' => '2026-10-10T09:00:00.000000Z'],
            array_intersect_key(self::recordAt($facts, 0, 'detail', 'payload'), ['revision' => true, 'present' => true, 'status' => true, 'notedAt' => true]),
        );
    }

    public function testAForgottenContactPublishesItsTerminalFacts(): void
    {
        $this->requestJson('DELETE', '/api/contacts/'.$this->contact);

        $events = $this->eventsOf('personal_data');
        $facts = array_values(array_filter($events, static fn (array $event): bool => 'prospecter.personal-data ContactFactsChanged.v2' === self::nameOf($event)));
        $this->assertCount(1, $facts);
        $this->assertSame(
            ['personId' => $this->contact, 'revision' => 0, 'present' => false, 'hasEmail' => false, 'hasPhone' => false, 'hasMobile' => false, 'hasLinkedIn' => false],
            array_intersect_key(self::recordAt($facts, 0, 'detail', 'payload'), ['personId' => true, 'revision' => true, 'present' => true, 'hasEmail' => true, 'hasPhone' => true, 'hasMobile' => true, 'hasLinkedIn' => true]),
        );
    }

    public function testTheItemOfOutboxExpiresAWeekAfterItsCommit(): void
    {
        $this->requestJson('DELETE', '/api/organizations/'.$this->organization);

        $item = $this->outboxOf('prospecting')[0];
        $this->assertSame('OUTBOX', $item['SK']);
        $this->assertSame(Documents::momentOf(self::stringAt($item, 'occurredAt'))->modify('+7 days')->getTimestamp(), $item['expiresAt']);
    }

    /**
     * The events the first item of outbox of a table carries.
     *
     * @return list<array<string, mixed>>
     */
    private function eventsOf(string $table): array
    {
        return self::records(json_decode(self::stringAt($this->outboxOf($table), 0, 'events'), true, flags: \JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $event
     */
    private static function nameOf(array $event): string
    {
        return self::stringAt($event, 'source').' '.self::stringAt($event, 'detailType');
    }
}
