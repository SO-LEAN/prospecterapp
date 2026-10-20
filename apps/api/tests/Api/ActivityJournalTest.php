<?php

namespace Tests\App\Api;

use App\Demo\DemoData;
use App\Shared\Persistence\Journal\ActivityJournal;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * Every domain event lands in the journal of its context, with who did it and to what (ADR 0043).
 */
#[Group('api')]
class ActivityJournalTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAnIntentionOfAnOrganizationIsKeptInTheJournalOfProspecting(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        $this->requestJson('PATCH', '/api/organizations/'.$organization.'/observations', ['observations' => 'Rappeler lundi']);

        $this->assertSame(['ProspectingStarted', 'ObservationsWritten'], $this->journalOf('prospecting', $organization));
    }

    public function testACollectedContactIsKeptInBothContextsItChanged(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$organization]])['id'];

        $this->assertSame(['ContactCollected'], $this->journalOf('personal_data', $contact));
        $this->assertSame(['ProspectingStarted', 'ContactAdded'], $this->journalOf('prospecting', $organization));
    }

    public function testAnExchangeIsKeptInTheJournalOfProspecting(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$organization]])['id'];

        $exchange = $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', ['channel' => 'sms', 'direction' => 'inbound'])['items'][0]['id'];

        $this->assertSame(['ExchangeNoted'], $this->journalOf('prospecting', $exchange));
    }

    public function testTheJournalKeepsTheCorrectionsOfAnExchangeOnceErased(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$organization]])['id'];
        $exchange = $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', ['channel' => 'sms', 'direction' => 'inbound'])['items'][0]['id'];

        $this->requestJson('PATCH', '/api/exchanges/'.$exchange, ['channel' => 'email', 'direction' => 'inbound', 'occurredAt' => '2026-10-15T08:00:00+00:00']);
        $this->requestJson('DELETE', '/api/exchanges/'.$exchange);

        $this->assertSame(['ExchangeNoted', 'ExchangeCorrected', 'ExchangeErased'], $this->journalOf('prospecting', $exchange));
    }

    public function testTheJournalNamesTheMemberWhoDidIt(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        $entries = $this->entriesOf('prospecting', $organization);

        $this->assertSame(DemoData::PROSPECTOR, $entries[0]['memberId']);
    }

    /**
     * @return list<string> the events, oldest first
     */
    private function journalOf(string $context, string $subject): array
    {
        return array_column($this->entriesOf($context, $subject), 'event');
    }

    /**
     * @return list<array{event: string, accountId: string, memberId: string, subjectId: string, occurredAt: string}>
     */
    private function entriesOf(string $context, string $subject): array
    {
        return self::getContainer()->get(ActivityJournal::class)->of(self::getContainer()->get('app.table.'.$context), $subject);
    }
}
