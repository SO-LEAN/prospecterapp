<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\CallsTheMcpServer;

/**
 * An AI notes the exchanges it reads in a mailbox or a messaging, each once (ADR 0166): noted again, it is answered
 * the exchange noted first, or who erased it, never an error.
 */
#[Group('api')]
class NotingToolsApiTest extends ApiTestCase
{
    use CallsTheMcpServer;

    private const array AN_EMAIL = ['channel' => 'email', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T09:12:00+00:00', 'text' => 'Votre devis', 'messageId' => '<abc@acme.fr>'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheAiNotesAnEMailWithItsMessageId(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));

        $noted = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

        $this->assertSame('noted', $noted['structuredContent']['status']);
        $timeline = $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges');
        $this->assertSame([$noted['structuredContent']['exchange']['id']], array_column($timeline['items'], 'id'));
    }

    public function testAnExchangeTheAiNotesIsCreatedByAnAiInTheTimeline(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));

        $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

        $timeline = $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges');
        $this->assertTrue($timeline['items'][0]['createdByAnAI'] ?? null);
    }

    public function testAnExchangeTheAiNotesIsCreatedByAnAiInItsDetail(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));

        $noted = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

        $this->assertTrue($noted['structuredContent']['exchange']['createdByAnAI'] ?? null);
        $this->assertTrue($this->requestJson('GET', '/api/exchanges/'.$noted['structuredContent']['exchange']['id'])['createdByAnAI'] ?? null);
    }

    public function testAnExchangeTheAiNotesWithAnOrganizationIsCreatedByAnAi(): void
    {
        $organization = $this->anOrganization('ACME');

        $noted = $this->callWithoutHandshake('log-organization-exchange', ['id' => $organization, 'channel' => 'call', 'direction' => 'inbound']);

        $this->assertTrue($noted['structuredContent']['exchange']['createdByAnAI'] ?? null);
    }

    public function testAnEMailNotedAgainIsAnsweredTheExchangeNotedFirstWithoutError(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $first = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

        $again = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact, 'messageId' => '<ABC@acme.fr>'] + self::AN_EMAIL);

        $this->assertFalse($again['isError'] ?? false);
        $this->assertSame('alreadyNoted', $again['structuredContent']['status']);
        $this->assertSame($first['structuredContent']['exchange']['id'], $again['structuredContent']['exchange']['id']);
        $this->assertCount(1, $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items']);
    }

    public function testAnEMailErasedIsAnsweredWhoErasedItWithoutError(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $first = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);
        $this->requestJson('DELETE', '/api/exchanges/'.$first['structuredContent']['exchange']['id']);

        $again = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

        $this->assertFalse($again['isError'] ?? false);
        $this->assertSame('erasedBefore', $again['structuredContent']['status']);
        $this->assertSame(['firstName' => 'Camille', 'lastName' => 'Martin'], $again['structuredContent']['erasedBy']['name']);
        $this->assertSame('2026-10-15T09:00:00+00:00', $again['structuredContent']['erasedAt']);
        $this->assertSame([], $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items']);
    }

    public function testAMemberRefusedTheEndpointIsRefusedTheToolAlike(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');

        foreach (['assistant-user', 'member-user', 'stranger'] as $member) {
            $this->login($member);
            $problem = $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', self::AN_EMAIL);

            $refusal = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

            $this->assertTrue($refusal['isError'] ?? false, $member);
            $this->assertSame($problem, json_decode($refusal['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR), $member);
        }
    }

    public function testAnAdministratorNotesAsAProspector(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $this->login($this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin));

        $noted = $this->callWithoutHandshake('log-contact-exchange', ['id' => $contact] + self::AN_EMAIL);

        $this->assertSame('noted', $noted['structuredContent']['status']);
    }

    public function testAMessageWithAnOrganizationItselfNotedAgainIsAnsweredTheExchangeNotedFirst(): void
    {
        $organization = $this->anOrganization('ACME');
        $message = ['id' => $organization, 'channel' => 'malt', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T09:12:00+00:00', 'text' => 'Bonjour'];
        $first = $this->callWithoutHandshake('log-organization-exchange', $message);

        $again = $this->callWithoutHandshake('log-organization-exchange', ['text' => 'bonjour !'] + $message);

        $this->assertSame('alreadyNoted', $again['structuredContent']['status']);
        $this->assertSame($first['structuredContent']['exchange']['id'], $again['structuredContent']['exchange']['id']);
    }

    public function testAMemberRefusedTheEndpointOfAnOrganizationIsRefusedItsToolAlike(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');

        foreach (['assistant-user', 'member-user', 'stranger'] as $member) {
            $this->login($member);
            $problem = $this->requestJson('POST', '/api/organizations/'.$organization.'/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]);

            $refusal = $this->callWithoutHandshake('log-organization-exchange', ['id' => $organization, 'channel' => 'call', 'direction' => 'inbound']);

            $this->assertTrue($refusal['isError'] ?? false, $member);
            $this->assertSame($problem, json_decode($refusal['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR), $member);
        }
    }

    public function testAnAdministratorNotesWithAnOrganizationAsAProspector(): void
    {
        $organization = $this->anOrganization('ACME');
        $this->login($this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin));

        $noted = $this->callWithoutHandshake('log-organization-exchange', ['id' => $organization, 'channel' => 'call', 'direction' => 'inbound']);

        $this->assertSame('noted', $noted['structuredContent']['status']);
    }

    private function aContactOf(string $organization): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada'], 'organizationIds' => [$organization]])['id'];
    }

    private function anOrganization(string $name): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]])['id'];
    }
}
