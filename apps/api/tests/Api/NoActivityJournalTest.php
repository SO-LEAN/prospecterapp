<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * No activity journal is kept: a domain event is published, never written down for itself (ADR 0096).
 */
#[Group('api')]
class NoActivityJournalTest extends ApiTestCase
{
    private const array TABLES = ['app.table.accounts', 'app.table.personal_data', 'app.table.prospecting'];

    public function testACommandWritesNoItemOfJournal(): void
    {
        $this->login();
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $contact = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$organization]])['id'];

        $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', ['channel' => 'sms', 'direction' => 'inbound']);

        $this->assertResponseIsSuccessful();
        foreach (self::TABLES as $table) {
            $this->assertSame([], self::getContainer()->get($table)->scan('JOURNAL#'), $table);
        }
    }
}
