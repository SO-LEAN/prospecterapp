<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;

/**
 * The place of a meeting, the link of a video call: planned with them, read in the detail and in what is to come,
 * corrected by every prospector of the account; the place proposed from the organization of the contact. The clock
 * of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class ExchangeVenueApiTest extends ApiTestCase
{
    private const string NEXT_TUESDAY = '2026-10-20T14:00:00+00:00';
    private const string MEET = 'https://meet.google.com/abc-defg-hij';

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testAMeetingHasItsPlaceAndAVideoCallItsLinkInTheDetailAndWhatIsToCome(): void
    {
        $organization = $this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME']]);
        $ada = $this->aContactOf($organization);
        $this->plan($ada, ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY, 'place' => 'Café de Flore']);
        $this->planWith($organization, ['exchange' => ['channel' => 'videoCall', 'occurredAt' => '2026-10-21T09:00:00+00:00', 'link' => self::MEET]]);

        $upcoming = $this->requestJson('GET', '/api/organizations/'.$organization.'/upcoming-exchanges')['items'];

        $this->assertSame([['meeting', 'Café de Flore', null], ['videoCall', null, self::MEET]], array_map(static fn (array $item): array => [$item['exchange']['channel'], $item['exchange']['place'], $item['exchange']['link']], $upcoming));
        $meeting = $this->requestJson('GET', '/api/exchanges/'.$upcoming[0]['exchange']['id']);
        $this->assertSame(['Café de Flore', null], [$meeting['place'], $meeting['link']]);
        $this->assertSame(['href' => '/api/exchanges/'.$meeting['id'].'/venue', 'method' => 'PATCH'], $meeting['_links']['correctVenue']);
        $this->assertSame(self::MEET, $this->requestJson('GET', '/api/exchanges/'.$upcoming[1]['exchange']['id'])['link']);
    }

    public function testThePlaceAndTheLinkFollowTheRulesOfTheCore(): void
    {
        $ada = $this->aContactOf($this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME']]));

        $placeOfACall = $this->plan($ada, ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY, 'place' => 'Café de Flore']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['place'], 'placeOnlyForAMeeting'], [$this->violatedFields($placeOfACall), $placeOfACall['reason']]);
        $linkOfAMeeting = $this->plan($ada, ['channel' => 'meeting', 'occurredAt' => self::NEXT_TUESDAY, 'link' => self::MEET]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['link'], 'linkOnlyForAVideoCall'], [$this->violatedFields($linkOfAMeeting), $linkOfAMeeting['reason']]);
        $words = $this->plan($ada, ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY, 'link' => 'réunion de mardi']);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame([['link'], 'invalidVideoCallLink'], [$this->violatedFields($words), $words['reason']]);
    }

    /**
     * The place and the link are no personal data: every prospector corrects them, with the right to manage
     * personal data or not, an administrator too.
     */
    #[DataProvider('members')]
    public function testEveryProspectorAddsTheLinkOfAVideoCall(string $reference, Role ...$roles): void
    {
        $ada = $this->aContactOf($this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME']]));
        $videoCall = $this->plan($ada, ['channel' => 'videoCall', 'occurredAt' => self::NEXT_TUESDAY])['items'][0]['id'];
        $this->login($this->aMemberOfTheFixtures($reference, ...$roles));
        $this->assertArrayHasKey('correctVenue', $this->requestJson('GET', '/api/exchanges/'.$videoCall)['_links']);

        $corrected = $this->requestJson('PATCH', '/api/exchanges/'.$videoCall.'/venue', ['link' => self::MEET]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(self::MEET, $corrected['link']);
    }

    /**
     * @return iterable<string, list<string|Role>>
     */
    public static function members(): iterable
    {
        yield 'a prospector with the right to manage personal data' => ['prospector-with-the-right', Role::User, Role::Prospector, Role::PersonalData];
        yield 'a prospector without it' => ['prospector-without-the-right', Role::User, Role::Prospector];
        yield 'an administrator' => ['administrator', Role::Prospector, Role::Admin];
    }

    public function testOnlyAMeetingOrAVideoCallHasAVenueToCorrect(): void
    {
        $ada = $this->aContactOf($this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME']]));
        $call = $this->plan($ada, ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY])['items'][0]['id'];

        $this->assertArrayNotHasKey('correctVenue', $this->requestJson('GET', '/api/exchanges/'.$call)['_links']);
        $refused = $this->requestJson('PATCH', '/api/exchanges/'.$call.'/venue', ['place' => 'Café de Flore']);
        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('Only a meeting or a video call takes place somewhere', $refused['detail']);
    }

    public function testTheVenueOfAnExchangeOfAnotherAccountIsNotFound(): void
    {
        $this->requestJson('PATCH', '/api/exchanges/'.self::newIdentity().'/venue', ['place' => 'Café de Flore']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testACallIsMadeToTheNumberOfTheContactReadInClearOnly(): void
    {
        $organization = $this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME'], 'contactDetails' => ['phoneNumber' => '03 88 11 22 33']]);
        $ada = $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'contactDetails' => ['phoneNumber' => '03 88 00 00 42'], 'organizationIds' => [$organization]])['id'];
        $withAda = $this->plan($ada, ['channel' => 'call', 'occurredAt' => self::NEXT_TUESDAY])['items'][0]['id'];
        $this->planWith($organization, ['exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-21T09:00:00+00:00']]);
        $withAcme = $this->requestJson('GET', '/api/organizations/'.$organization.'/upcoming-exchanges')['items'][1]['exchange']['id'];

        $this->assertSame('+33 3 88 00 00 42', $this->requestJson('GET', '/api/exchanges/'.$withAda)['phoneNumber']);
        $this->assertSame('+33 3 88 11 22 33', $this->requestJson('GET', '/api/exchanges/'.$withAcme)['phoneNumber']);
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));
        $this->assertNull($this->requestJson('GET', '/api/exchanges/'.$withAda)['phoneNumber']);
    }

    public function testThePlaceProposedIsTheAddressOfTheOrganizationOfTheContact(): void
    {
        $ada = $this->aContactOf($this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME'], 'address' => ['street' => '12 rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'country' => 'FR']]));
        $this->login($this->aMemberOfTheFixtures('prospector-without-the-right', Role::User, Role::Prospector));

        $proposed = $this->requestJson('GET', '/api/contacts/'.$ada.'/meeting-place');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(['street' => '12 rue de la Paix', 'postalCode' => '75002', 'city' => 'Paris', 'country' => 'FR'], $proposed['address']);
    }

    public function testNoPlaceIsProposedWithoutAnAddress(): void
    {
        $ada = $this->aContactOf($this->anOrganization(['legalIdentity' => ['corporateName' => 'ACME']]));

        $this->assertSame(['address' => null], $this->requestJson('GET', '/api/contacts/'.$ada.'/meeting-place'));
    }

    public function testNoPlaceIsProposedForAPersonWhoIsNoContact(): void
    {
        $this->requestJson('GET', '/api/contacts/'.self::newIdentity().'/meeting-place');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @param array<string, mixed> $planned
     *
     * @return array<string, mixed>
     */
    private function plan(string $contact, array $planned): array
    {
        return $this->requestJson('POST', '/api/contacts/'.$contact.'/upcoming-exchanges', $planned);
    }

    /**
     * @param array<string, mixed> $planned
     */
    private function planWith(string $organization, array $planned): void
    {
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', $planned);
    }

    private function aContactOf(string $organization): string
    {
        return $this->requestJson('POST', '/api/contacts', ['name' => ['firstName' => 'Ada', 'lastName' => 'Lovelace'], 'organizationIds' => [$organization]])['id'];
    }

    /**
     * @param array<string, mixed> $organization
     */
    private function anOrganization(array $organization): string
    {
        return $this->requestJson('POST', '/api/organizations', $organization)['id'];
    }
}
