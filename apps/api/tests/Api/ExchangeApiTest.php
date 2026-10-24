<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\Prospecting\Domain\Attachment;
use Prospecter\Core\Prospecting\Domain\AttachmentId;
use Prospecter\Core\Prospecting\Domain\Channel;
use Prospecter\Core\Prospecting\Domain\Direction;
use Prospecter\Core\Prospecting\Domain\Exchange;
use Prospecter\Core\Prospecting\Domain\ExchangeFacts;
use Prospecter\Core\Prospecting\Domain\ExchangeId;
use Prospecter\Core\Prospecting\Domain\ExchangeRepository;
use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\Prospecting\Domain\OrganizationRepository;
use Prospecter\Core\Prospecting\Domain\Prospector;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;
use Tests\App\Base\ApiTestCase;

/**
 * The exchanges with a contact, noted and read in its timeline (ADR 0074); the clock of the tests says it is
 * 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ExchangeApiTest extends ApiTestCase
{
    private const string ATTACHMENT = '0190a1c0-0000-7000-8000-0000000000a1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testANotedExchangeIsAtTheTopOfTheTimeline(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $this->note($contact, ['channel' => 'call', 'direction' => 'outbound', 'occurredAt' => '2026-10-10T09:00:00+02:00']);

        $timeline = $this->note($contact, ['channel' => 'linkedIn', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+02:00', 'text' => 'Intéressée par une mission en mars']);

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('Location', '/api/exchanges/'.$timeline['items'][0]['id']);
        $this->assertSame([['linkedIn', 'inbound', '2026-10-14T12:30:00+00:00', 'Intéressée par une mission en mars'], ['call', 'outbound', '2026-10-10T07:00:00+00:00', null]], array_map(static fn (array $exchange): array => [$exchange['channel'], $exchange['direction'], $exchange['occurredAt'], $exchange['text']], $timeline['items']));
        $this->assertSame(['self', 'noteExchange'], array_keys($timeline['_links']));
        $this->assertSame(['href' => '/api/exchanges/'.$timeline['items'][0]['id'], 'method' => 'GET'], $timeline['items'][0]['_links']['self']);
        $this->assertSame(['href' => '/api/contacts/'.$contact.'/exchanges', 'method' => 'POST'], $timeline['_links']['noteExchange']);
        $this->assertSame($timeline, $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges'));
    }

    public function testAnExchangeHappensNowWhenItsMomentIsLeftOut(): void
    {
        $timeline = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'call', 'direction' => 'inbound', 'missed' => true]);

        $this->assertSame('2026-10-15T09:00:00+00:00', $timeline['items'][0]['occurredAt']);
        $this->assertTrue($timeline['items'][0]['missed']);
    }

    public function testTheExchangeIsKeptForTheOrganizationsOfTheContact(): void
    {
        $organization = $this->anOrganization('ACME');
        $group = $this->anOrganization('Groupe ACME');
        $contact = $this->aContactOf($organization);
        $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $contact]);

        $timeline = $this->note($contact, ['channel' => 'sms', 'direction' => 'inbound']);

        $this->assertEqualsCanonicalizing([$organization, $group], $timeline['items'][0]['organizationIds']);
    }

    public function testTheTimelineComesInSlices(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));

        foreach (range(1, 21) as $minutesAgo) {
            $this->note($contact, ['channel' => 'email', 'direction' => 'outbound', 'occurredAt' => sprintf('2026-10-15T08:%02d:00+00:00', 59 - $minutesAgo)]);
        }

        $first = $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges');
        $this->assertCount(20, $first['items']);
        $this->assertSame('2026-10-15T08:58:00+00:00', $first['items'][0]['occurredAt']);
        $following = $this->requestJson('GET', $first['_links']['next']['href']);
        $this->assertSame(['2026-10-15T08:38:00+00:00'], array_column($following['items'], 'occurredAt'));
        $this->assertArrayNotHasKey('next', $following['_links']);
    }

    public function testOnlyACallIsMissed(): void
    {
        $problem = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'sms', 'direction' => 'outbound', 'missed' => true]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['missed'], $this->violatedFields($problem));
        $this->assertSame('missedOnlyForACall', $problem['reason']);
    }

    public function testAnExchangeIsNeverInTheFuture(): void
    {
        $problem = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'email', 'direction' => 'inbound', 'occurredAt' => '2026-10-15T09:00:01+00:00']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['occurredAt'], $this->violatedFields($problem));
    }

    public function testAnUnknownChannelIsRefused(): void
    {
        $problem = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'pigeon', 'direction' => 'inbound']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['channel'], $this->violatedFields($problem));
    }

    public function testAPersonNoOrganizationOfMineNamesIsNotFound(): void
    {
        $this->note(self::newIdentity(), ['channel' => 'sms', 'direction' => 'inbound']);
        $this->assertResponseStatusCodeSame(404);

        $this->requestJson('GET', '/api/contacts/'.self::newIdentity().'/exchanges');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutTheRightTheTimelineIsReadItsTextsFlaggedButNoExchangeIsNoted(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $this->note($contact, ['channel' => 'call', 'direction' => 'inbound', 'text' => 'Ada rappellera lundi']);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $timeline = $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame([['Ada rappellera lundi', true]], array_map(static fn (array $exchange): array => [$exchange['text'], $exchange['textFlagged']], $timeline['items']));
        $this->assertSame(['self'], array_keys($timeline['_links']));
        $this->note($contact, ['channel' => 'sms', 'direction' => 'inbound']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnOrganizationNoLongerProspectedTakesItsExchangesAlong(): void
    {
        $organization = $this->anOrganization('ACME');
        $group = $this->anOrganization('Groupe ACME');
        $alone = $this->aContactOf($organization);
        $shared = $this->aContactOf($organization, 'Grace');
        $this->requestJson('POST', '/api/organizations/'.$group.'/contacts', ['personId' => $shared]);
        $this->note($alone, ['channel' => 'sms', 'direction' => 'inbound']);
        $this->note($shared, ['channel' => 'call', 'direction' => 'outbound']);

        $this->requestJson('DELETE', '/api/organizations/'.$organization);
        $this->assertResponseStatusCodeSame(204);

        $timeline = $this->requestJson('GET', '/api/contacts/'.$shared.'/exchanges');
        $this->assertSame([[$group]], array_column($timeline['items'], 'organizationIds'));
    }

    public function testTheDetailShowsTheWholeExchangeAndWhoNotedIt(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $timeline = $this->note($contact, ['channel' => 'sms', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+00:00', 'text' => "Rappeler lundi\nAprès le salon"]);

        $exchange = $this->requestJson('GET', $timeline['items'][0]['_links']['self']['href']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['kind' => 'person', 'id' => $contact, 'name' => 'Ada', 'presence' => 'present'], $exchange['interlocutor']);
        $this->assertSame(['sms', 'inbound', false, '2026-10-14T14:30:00+00:00', "Rappeler lundi\nAprès le salon", false], [$exchange['channel'], $exchange['direction'], $exchange['missed'], $exchange['occurredAt'], $exchange['text'], $exchange['textFlagged']]);
        $this->assertSame(['at' => '2026-10-15T09:00:00+00:00', 'by' => ['name' => ['firstName' => null, 'lastName' => null], 'email' => 'prospector@solean-it.io']], $exchange['noted']);
        $this->assertNull($exchange['corrected']);
        $this->assertSame([], $exchange['attachments']);
        $this->assertSame(['self', 'interlocutor', 'correctExchange', 'correctText', 'eraseExchange', 'attachFile'], array_keys($exchange['_links']));
        $this->assertSame(['href' => '/api/contacts/'.$contact, 'method' => 'GET'], $exchange['_links']['interlocutor']);
        $this->assertSame(['href' => '/api/exchanges/'.$exchange['id'].'/facts', 'method' => 'PATCH'], $exchange['_links']['correctExchange']);
        $this->assertSame(['href' => '/api/exchanges/'.$exchange['id'].'/text', 'method' => 'PATCH'], $exchange['_links']['correctText']);
        $this->assertSame(['href' => '/api/exchanges/'.$exchange['id'], 'method' => 'DELETE'], $exchange['_links']['eraseExchange']);
    }

    public function testAnExchangeWithTheOrganizationItselfHasItForInterlocutor(): void
    {
        $organization = $this->anOrganization('ACME');
        $timeline = $this->requestJson('POST', '/api/organizations/'.$organization.'/exchanges', ['exchange' => ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+00:00'], 'personId' => null]);
        $id = $timeline['items'][0]['exchange']['id'];

        $exchange = $this->correct($id, ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+00:00', 'text' => 'Le standard rappellera']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['kind' => 'organization', 'id' => $organization, 'name' => 'ACME', 'presence' => null], $exchange['interlocutor']);
        $this->assertSame('Le standard rappellera', $exchange['text']);
        $this->assertSame(['href' => '/api/organizations/'.$organization, 'method' => 'GET'], $exchange['_links']['interlocutor']);
    }

    public function testACorrectionIsShownWithWhoMadeIt(): void
    {
        $contact = $this->aContactOf($this->anOrganization('ACME'));
        $id = $this->note($contact, ['channel' => 'linkedIn', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+00:00', 'text' => 'Intéressée'])['items'][0]['id'];

        $exchange = $this->correct($id, ['channel' => 'email', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+00:00', 'text' => 'Objet : mission. Intéressée']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['email', 'Objet : mission. Intéressée'], [$exchange['channel'], $exchange['text']]);
        $this->assertSame('2026-10-15T09:00:00+00:00', $exchange['corrected']['at']);
        $this->assertSame('prospector@solean-it.io', $exchange['corrected']['by']['email']);
        $this->assertSame([['email', 'Objet : mission. Intéressée']], array_map(static fn (array $item): array => [$item['channel'], $item['text']], $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items']));
    }

    public function testACorrectionIsNeverInTheFuture(): void
    {
        $id = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'call', 'direction' => 'inbound'])['items'][0]['id'];

        $problem = $this->correct($id, ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-16T09:00:00+00:00']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['occurredAt'], $this->violatedFields($problem));
    }

    public function testTheContactOfAnExchangeIsNotCorrected(): void
    {
        $id = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'call', 'direction' => 'inbound'])['items'][0]['id'];

        $problem = $this->correct($id, ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-15T08:00:00+00:00', 'personId' => self::newIdentity()]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['personId'], $this->violatedFields($problem));
    }

    public function testAnErasedExchangeLeavesEveryTimeline(): void
    {
        $organization = $this->anOrganization('ACME');
        $contact = $this->aContactOf($organization);
        $id = $this->note($contact, ['channel' => 'sms', 'direction' => 'inbound'])['items'][0]['id'];

        $this->requestJson('DELETE', '/api/exchanges/'.$id);

        $this->assertResponseStatusCodeSame(204);
        $this->assertSame([], $this->requestJson('GET', '/api/contacts/'.$contact.'/exchanges')['items']);
        $this->requestJson('GET', '/api/exchanges/'.$id);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutTheRightAnExchangeIsReadMaskedAndOnlyItsTextCorrected(): void
    {
        $id = $this->note($this->aContactOf($this->anOrganization('ACME'), 'Grace'), ['channel' => 'sms', 'direction' => 'inbound', 'text' => 'Grace rappellera lundi'])['items'][0]['id'];
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $exchange = $this->requestJson('GET', '/api/exchanges/'.$id);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('G***e', $exchange['interlocutor']['name']);
        $this->assertSame(['Grace rappellera lundi', true], [$exchange['text'], $exchange['textFlagged']]);
        $this->assertSame(['self', 'interlocutor', 'correctText'], array_keys($exchange['_links']));
        $this->correct($id, ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-15T08:00:00+00:00']);
        $this->assertResponseStatusCodeSame(403);
        $this->requestJson('DELETE', '/api/exchanges/'.$id);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheTextCorrectedAloneIsShownWithWhoCorrectedIt(): void
    {
        $id = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'sms', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T14:30:00+00:00', 'text' => 'Ada rappellera lundi'])['items'][0]['id'];
        $this->login('assistant-user');

        $exchange = $this->requestJson('PATCH', '/api/exchanges/'.$id.'/text', ['text' => 'La contactée rappellera lundi']);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['sms', '2026-10-14T14:30:00+00:00', 'La contactée rappellera lundi'], [$exchange['channel'], $exchange['occurredAt'], $exchange['text']]);
        $this->assertSame('2026-10-15T09:00:00+00:00', $exchange['corrected']['at']);
        $this->assertSame('assistant@solean-it.io', $exchange['corrected']['by']['email']);
    }

    public function testOnlyTheTextIsCorrectedThere(): void
    {
        $id = $this->note($this->aContactOf($this->anOrganization('ACME')), ['channel' => 'sms', 'direction' => 'inbound'])['items'][0]['id'];

        $problem = $this->requestJson('PATCH', '/api/exchanges/'.$id.'/text', ['text' => 'Rien', 'channel' => 'call']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['channel'], $this->violatedFields($problem));
    }

    public function testAnExchangeNeverNotedIsNotFound(): void
    {
        $this->requestJson('GET', '/api/exchanges/'.self::newIdentity());
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAnExchangeOfAnotherAccountIsNotFoundForEveryIntention(): void
    {
        $id = $this->anExchangeOfAnotherAccount();
        $attachment = '/attachments/'.self::ATTACHMENT;
        $intentions = [
            ['GET', '', null], ['PATCH', '/facts', ['channel' => 'call', 'direction' => 'inbound', 'occurredAt' => '2026-10-14T08:00:00+00:00']], ['PATCH', '/text', ['text' => 'Rien']],
            ['DELETE', '', null], ['POST', '/attachments', ['upload' => self::newIdentity(), 'name' => 'devis.pdf']],
            ['GET', $attachment, null], ['DELETE', $attachment, null],
        ];

        foreach ($intentions as [$method, $path, $payload]) {
            $problem = $this->requestJson($method, '/api/exchanges/'.$id.$path, $payload);
            $this->assertResponseStatusCodeSame(404, $method.' '.$path);
            $this->assertSame('unknownExchange', $problem['reason'], $method.' '.$path);
        }
    }

    /**
     * @param array<string, mixed> $facts
     *
     * @return array<string, mixed>
     */
    private function correct(string $id, array $facts): array
    {
        return $this->requestJson('PATCH', '/api/exchanges/'.$id.'/facts', $facts);
    }

    /**
     * @param array<string, mixed> $exchange
     *
     * @return array<string, mixed>
     */
    private function note(string $contact, array $exchange): array
    {
        return $this->requestJson('POST', '/api/contacts/'.$contact.'/exchanges', $exchange);
    }

    private function aContactOf(string $organization, string $firstName = 'Ada'): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => $firstName], 'organizationIds' => [$organization]])['id'];
    }

    private function anOrganization(string $name): string
    {
        return $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => $name]])['id'];
    }

    private function anExchangeOfAnotherAccount(): string
    {
        $competitor = Account::open(new AccountId(self::newIdentity()), new LegalIdentity('Competitor'), new \DateTimeImmutable());
        self::getContainer()->get(AccountRepository::class)->save($competitor);
        $theirs = Organization::prospectedBy(new Prospector(new MemberId(self::newIdentity()), $competitor->id()), new OrganizationId(self::newIdentity()), new LegalIdentity('Their prospect'), new ContactDetails(), new \DateTimeImmutable());
        self::getContainer()->get(OrganizationRepository::class)->save($theirs);
        $exchange = Exchange::notedWithTheOrganization($theirs, new MemberId(self::newIdentity()), new ExchangeId(self::newIdentity()), new ExchangeFacts(Channel::Call, Direction::Inbound, new \DateTimeImmutable('2026-10-14')), new \DateTimeImmutable('2026-10-14'));
        $exchange->attach(new Attachment(new AttachmentId(self::ATTACHMENT), 'devis.pdf', 2048, 'application/pdf', 'their-file'), new MemberId(self::newIdentity()), new \DateTimeImmutable('2026-10-14'));
        self::getContainer()->get(ExchangeRepository::class)->save($exchange);

        return $exchange->id()->value;
    }
}
