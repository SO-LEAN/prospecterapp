<?php

namespace Tests\App\Api;

use App\DataFixtures\AppFixtures;
use App\Shared\Persistence\Journal\JournalEntry;
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

    public function testTheJournalNamesTheMemberWhoDidIt(): void
    {
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];

        $entries = $this->entriesOf('prospecting', $organization);

        $this->assertSame(AppFixtures::PROSPECTOR, $entries[0]->memberId);
    }

    /**
     * @return list<string> the events, oldest first
     */
    private function journalOf(string $context, string $subject): array
    {
        return array_map(static fn (JournalEntry $entry): string => $entry->event, $this->entriesOf($context, $subject));
    }

    /**
     * @return list<JournalEntry>
     */
    private function entriesOf(string $context, string $subject): array
    {
        return self::getContainer()->get('doctrine')->getManager($context)->getRepository(JournalEntry::class)->findBy(['subjectId' => $subject], ['occurredAt' => 'ASC', 'id' => 'ASC']);
    }
}
