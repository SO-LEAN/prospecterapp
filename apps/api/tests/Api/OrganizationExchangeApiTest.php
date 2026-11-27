<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

/**
 * The timeline of an organization: its exchanges and those of its contacts, each with who communicated, noted
 * from its card (ADR 0076); the clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class OrganizationExchangeApiTest extends ApiTestCase
{
    use ReadsResources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testTheTimelineGathersTheExchangesOfTheOrganizationAndOfItsContactsWithWhoCommunicated(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $robert = $this->aContactOf($organization, 'Robert', 'Noyce');
        $this->requestJson('POST', '/api/contacts/'.$ada.'/exchanges', ['channel' => 'email', 'direction' => 'inbound', 'occurredAt' => '2026-10-10T09:00:00+00:00']);
        $this->note($organization, ['personId' => $robert, 'exchange' => ['channel' => 'call', 'direction' => 'outbound', 'occurredAt' => '2026-10-12T11:00:00+00:00']]);

        $timeline = $this->note($organization, ['exchange' => ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T16:00:00+00:00']]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/exchanges/'.self::stringAt($timeline, 'items', 0, 'exchange', 'id'));
        $this->assertSame([
            [['kind' => 'organization', 'id' => $organization, 'name' => 'ACME', 'presence' => null], 'call', 'inbound'],
            [['kind' => 'person', 'id' => $robert, 'name' => 'Robert Noyce', 'presence' => 'present'], 'call', 'outbound'],
            [['kind' => 'person', 'id' => $ada, 'name' => 'Ada Lovelace', 'presence' => 'present'], 'email', 'inbound'],
        ], array_map(static fn (array $item): array => [$item['interlocutor'], self::valueAt($item, 'exchange', 'channel'), self::valueAt($item, 'exchange', 'direction')], self::recordsAt($timeline, 'items')));
        $this->assertSame(['self', 'noteExchange', 'planExchange'], array_keys(self::recordAt($timeline, '_links')));
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/exchanges', 'method' => 'POST'], self::valueAt($timeline, '_links', 'noteExchange'));
        $this->assertSame($timeline, $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges'));
    }

    #[Group('core-rule')]
    public function testAnExchangeWithTheOrganizationItselfIsInTheTimelineOfNoContact(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->aContactOf($organization, 'Ada', 'Lovelace');

        $this->note($organization, ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]);

        $this->assertSame([], $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items']);
    }

    public function testAnExchangeWithAnOrganizationNoLongerKnownIsReadWithoutNamingItNorLinkingIt(): void
    {
        $organization = $this->anOrganization('ACME');
        $exchange = self::stringAt($this->note($organization, ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]), 'items', 0, 'exchange', 'id');
        self::tableOf('prospecting')->batch([], [['ORGANIZATION#'.$organization, 'ORGANIZATION']]);

        $detail = $this->requestJson('GET', '/api/exchanges/'.$exchange);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['kind' => 'organization', 'id' => $organization, 'name' => null, 'presence' => null], false], [$detail['interlocutor'], array_key_exists('interlocutor', self::recordAt($detail, '_links'))]);
    }

    public function testOnlyALinkedInMessageIsNotedWithItsConversation(): void
    {
        $problem = $this->note($this->anOrganization('ACME'), ['exchange' => ['channel' => 'email', 'direction' => 'inbound', 'linkedInConversation' => '2-abc']]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('referenceNotOfTheChannel', $problem['reason']);
    }

    #[Group('core-rule')]
    public function testAnExchangeNotedWithAContactFromTheOrganizationIsInTheTimelineOfTheContact(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->aContactOf($organization, 'Robert', 'Noyce');

        $this->note($organization, ['personId' => $contact, 'exchange' => ['channel' => 'email', 'direction' => 'outbound']]);

        $this->assertSame([[$organization]], array_column(self::recordsAt($this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges'), 'items'), 'organizationIds'));
    }

    public function testTheInterlocutorChosenFromAnOrganizationIsOneOfItsContacts(): void
    {
        $organization = $this->anOrganization('ACME');
        $elsewhere = $this->aContactOf($this->anOrganization('Globex'), 'Grace', 'Hopper');

        $problem = $this->note($organization, ['personId' => $elsewhere, 'exchange' => ['channel' => 'email', 'direction' => 'outbound']]);

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame(['personId'], $this->violatedFields($problem));
        $this->assertSame('notAContact', $problem['reason']);
    }

    public function testTheTimelineComesInSlices(): void
    {
        $organization = $this->anOrganization('ACME');

        foreach (range(1, 21) as $minutesAgo) {
            $this->note($organization, ['exchange' => ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => sprintf('2026-10-15T08:%02d:00+00:00', 59 - $minutesAgo)]]);
        }

        $first = $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges');
        $this->assertCount(20, self::arrayAt($first, 'items'));
        $following = $this->requestJson('GET', self::stringAt($first, '_links', 'next', 'href'));
        $this->assertSame(['2026-10-15T08:38:00+00:00'], array_map(static fn (array $item): string => self::stringAt($item, 'exchange', 'occurredAt'), self::recordsAt($following, 'items')));
        $this->assertArrayNotHasKey('next', self::recordAt($following, '_links'));
    }

    public function testTheCardOfAnOrganizationLeadsToItsTimeline(): void
    {
        $organization = $this->anOrganization('ACME');

        $links = self::recordAt($this->requestJson('GET', '/api/organizations/'.$organization), '_links');

        $this->assertSame(['href' => '/api/organizations/'.$organization.'/exchanges', 'method' => 'GET'], $links['exchanges']);
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/exchanges', 'method' => 'POST'], $links['noteExchange']);
    }

    public function testWithoutTheRightTheTimelineIsReadItsContactsNamedAndNothingToNoteLinked(): void
    {
        $organization = $this->anOrganization('ACME');
        $grace = $this->aContactOf($organization, 'Grace', 'Hopper');
        $this->note($organization, ['exchange' => ['channel' => 'call', 'direction' => 'inbound', 'text' => 'Grace rappellera lundi'], 'personId' => $grace]);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $links = self::recordAt($this->requestJson('GET', '/api/organizations/'.$organization), '_links');
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/exchanges', 'method' => 'GET'], $links['exchanges']);
        $this->assertArrayNotHasKey('noteExchange', $links);
        $timeline = $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['Grace Hopper', true]], array_map(static fn (array $item): array => [self::valueAt($item, 'interlocutor', 'name'), self::valueAt($item, 'exchange', 'textFlagged')], self::recordsAt($timeline, 'items')));
        $this->assertSame(['self', 'planExchange'], array_keys(self::recordAt($timeline, '_links')));
    }

    #[Group('core-rule')]
    public function testAContactRemovedFromTheOrganizationStaysInItsTimelineAsRemoved(): void
    {
        $organization = $this->anOrganization('ACME');
        $group = $this->anOrganization('Groupe ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace', $group);
        $this->note($organization, ['personId' => $ada, 'exchange' => ['channel' => 'email', 'direction' => 'inbound']]);

        $this->requestJson('DELETE', '/api/organizations/'.$organization.'/contacts/'.$ada);

        $this->assertSame([['kind' => 'person', 'id' => $ada, 'name' => 'Ada Lovelace', 'presence' => 'removed']], array_column(self::recordsAt($this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges'), 'items'), 'interlocutor'));
        $this->assertSame(['present'], array_column(array_column(self::recordsAt($this->requestJson('GET', '/api/organizations/'.$group.'/exchanges'), 'items'), 'interlocutor'), 'presence'));
    }

    public function testTheExchangesOfAForgottenContactStayToMakeConform(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $this->note($organization, ['personId' => $ada, 'exchange' => ['channel' => 'email', 'direction' => 'inbound', 'text' => 'Ada, 06 12 34 56 78, attend notre devis']]);
        $this->note($organization, ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]);

        $this->requestJson('DELETE', '/api/contacts/'.$ada);

        $timeline = $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges');
        $this->assertSame(1, $timeline['toRedact']);
        $this->assertSame(['href' => '/api/organizations/'.$organization.'/exchanges-to-redact', 'method' => 'GET'], self::valueAt($timeline, '_links', 'toRedact'));
        $toMakeConform = $this->requestJson('GET', self::stringAt($timeline, '_links', 'toRedact', 'href'));
        $this->assertSame([[['kind' => 'person', 'id' => $ada, 'name' => null, 'presence' => 'forgotten'], 'Ada, 06 12 34 56 78, attend notre devis', true, true]], array_map(static fn (array $item): array => [$item['interlocutor'], self::valueAt($item, 'exchange', 'text'), self::valueAt($item, 'exchange', 'textToRedact'), self::valueAt($item, 'exchange', 'toRedact')], self::recordsAt($toMakeConform, 'items')));
        $this->assertSame(['self', 'noteExchange', 'planExchange', 'toRedact'], array_keys(self::recordAt($toMakeConform, '_links')));
    }

    public function testAnExchangeOfAForgottenContactIsMadeConformByCorrectingItsText(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $exchange = self::stringAt($this->note($organization, ['personId' => $ada, 'exchange' => ['channel' => 'email', 'direction' => 'inbound', 'text' => 'Ada attend notre devis']]), 'items', 0, 'exchange', 'id');
        $this->requestJson('DELETE', '/api/contacts/'.$ada);

        $detail = $this->requestJson('GET', '/api/exchanges/'.$exchange);
        $this->assertArrayNotHasKey('interlocutor', self::recordAt($detail, '_links'));
        $this->assertSame(['href' => '/api/exchanges/'.$exchange.'/text', 'method' => 'PATCH'], self::valueAt($detail, '_links', 'redact'));
        $corrected = $this->requestJson('PATCH', self::stringAt($detail, '_links', 'redact', 'href'), ['text' => 'La directrice attend notre devis', 'redacted' => true]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([false, false, '2026-10-15T09:00:00+00:00'], [$corrected['textToRedact'], $corrected['toRedact'], self::valueAt($corrected, 'redacted', 'at')]);
        $this->assertArrayNotHasKey('redact', self::recordAt($corrected, '_links'));
        $this->assertSame(0, $this->requestJson('GET', '/api/organizations/'.$organization.'/exchanges')['toRedact']);
        $problem = $this->requestJson('PATCH', '/api/exchanges/'.$exchange.'/text', ['text' => 'Encore', 'redacted' => true]);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('nothingToRedact', $problem['reason']);
    }

    public function testARedactedExchangeNamesWhoRedactedItOnceCorrectedByAnother(): void
    {
        $organization = $this->anOrganization('ACME');
        $ada = $this->aContactOf($organization, 'Ada', 'Lovelace');
        $exchange = self::stringAt($this->note($organization, ['personId' => $ada, 'exchange' => ['channel' => 'email', 'direction' => 'inbound', 'text' => 'Ada attend notre devis']]), 'items', 0, 'exchange', 'id');
        $this->requestJson('DELETE', '/api/contacts/'.$ada);
        $this->login('assistant-user');
        $this->requestJson('PATCH', '/api/exchanges/'.$exchange.'/text', ['text' => 'La directrice attend notre devis', 'redacted' => true]);
        $this->login('prospector-user');

        $corrected = $this->requestJson('PATCH', '/api/exchanges/'.$exchange.'/text', ['text' => 'La directrice attend toujours notre devis']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['prospector@solean-it.io', 'assistant@solean-it.io'], [self::valueAt($corrected, 'corrected', 'by', 'email'), self::valueAt($corrected, 'redacted', 'by', 'email')]);
    }

    public function testAnOrganizationOfAnotherAccountIsNotFound(): void
    {
        $this->note(self::newIdentity(), ['exchange' => ['channel' => 'call', 'direction' => 'inbound']]);
        $this->assertResponseStatusCodeSame(404);

        $this->requestJson('GET', '/api/organizations/'.self::newIdentity().'/exchanges');
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, mixed> $noted the exchange, and the person it was with unless with the organization itself
     *
     * @return array<string, mixed>
     */
    private function note(string $organization, array $noted): array
    {
        return $this->requestJson('POST', '/api/organizations/'.$organization.'/exchanges', $noted);
    }

    private function aContactOf(string $organization, string $firstName, string $lastName, string ...$others): string
    {
        return self::stringAt($this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName, 'lastName' => $lastName], 'organizationIds' => [$organization, ...$others]]), 'id');
    }

    private function anOrganization(string $name): string
    {
        return self::stringAt($this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]]), 'id');
    }
}
