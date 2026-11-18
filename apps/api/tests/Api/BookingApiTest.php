<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * My booking page, mine only whatever my role (ADR 0148, 0151): my windows, my inactive periods, my channels, opened
 * only with Google connected, closed, its link changed; and the page a link leads to, read without signing in. The
 * clock of the tests says it is 2026-10-15 09:00 UTC.
 */
#[Group('api')]
class BookingApiTest extends ApiTestCase
{
    private const string CODE_OF_GOOGLE = 'a-code-of-google';
    private const string BASE62_OF_22 = '/^[0-9A-Za-z]{22}$/';
    private const array NOTHING_SHOWN = ['open' => false, 'host' => null, 'channels' => [], 'timeZone' => null, 'days' => []];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
    }

    #[DataProvider('members')]
    public function testEveryMemberReadsTheirPageNeverSetClosedAndEmpty(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));

        $page = $this->requestJson('GET', '/api/me/booking-page?timeZone=Europe/Paris');

        $this->assertResponseIsSuccessful();
        $this->assertSame([false, [], null, [], [], null, false], [$page['open'], $page['windows'], $page['timeZone'], $page['inactivePeriods'], $page['channels'], $page['code'], $page['calendarConnected']]);
        $this->assertSame(['self', 'setWindows', 'addInactivePeriod', 'offerChannels', 'open'], array_keys($page['_links']));
        $this->assertSame(['href' => '/api/me/booking-page/opening', 'method' => 'POST'], $page['_links']['open']);
    }

    /**
     * @return iterable<string, list<mixed>>
     */
    public static function members(): iterable
    {
        yield 'a prospector with the personal data' => ['prospector-user'];
        yield 'a prospector without the personal data' => ['assistant-user'];
        yield 'an administrator' => ['administrator', ...Role::cases()];
    }

    #[DataProvider('members')]
    public function testEveryMemberSetsTheirPageAndOpensItWithGoogleConnected(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));
        $this->connectGoogle();

        $this->requestJson('PATCH', '/api/me/booking-page/windows', ['timeZone' => 'Europe/Paris', 'windows' => [['weekday' => 'monday', 'from' => '14:00', 'to' => '17:00'], ['weekday' => 'thursday', 'from' => '09:00', 'to' => '12:00']]]);
        $this->assertResponseIsSuccessful();
        $this->requestJson('PATCH', '/api/me/booking-page/channels', ['channels' => ['call', 'videoCall']]);
        $this->assertResponseIsSuccessful();
        $page = $this->requestJson('POST', '/api/me/booking-page/opening');

        $this->assertResponseIsSuccessful();
        $this->assertTrue($page['open']);
        $this->assertTrue($page['calendarConnected']);
        $this->assertSame([['weekday' => 'monday', 'from' => '14:00', 'to' => '17:00'], ['weekday' => 'thursday', 'from' => '09:00', 'to' => '12:00']], $page['windows']);
        $this->assertSame('Europe/Paris', $page['timeZone']);
        $this->assertSame(['call', 'videoCall'], $page['channels']);
        $this->assertMatchesRegularExpression(self::BASE62_OF_22, (string) $page['code']);
        $this->assertSame(['self', 'setWindows', 'addInactivePeriod', 'offerChannels', 'close', 'changeLink'], array_keys($page['_links']));
    }

    #[DataProvider('members')]
    public function testEveryMemberAddsAndRemovesAPeriodClosesTheirPageAndChangesItsLink(string $member, Role ...$roles): void
    {
        $this->login([] === $roles ? $member : $this->aMemberOfTheFixtures($member, ...$roles));
        $this->connectGoogle();
        $this->requestJson('PATCH', '/api/me/booking-page/channels', ['channels' => ['videoCall']]);
        $former = $this->requestJson('POST', '/api/me/booking-page/opening')['code'];

        $period = $this->requestJson('POST', '/api/me/booking-page/inactive-periods', ['firstDay' => '2026-12-21', 'lastDay' => '2027-01-04'])['inactivePeriods'][0];
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame([], $this->requestJson('DELETE', $period['_links']['remove']['href'])['inactivePeriods']);
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->requestJson('DELETE', '/api/me/booking-page/opening')['open']);
        $this->assertResponseIsSuccessful();
        $changed = $this->requestJson('POST', '/api/me/booking-page/link');

        $this->assertResponseIsSuccessful();
        $this->assertNotSame($former, $changed['code']);
        $this->assertSame(['self', 'setWindows', 'addInactivePeriod', 'offerChannels', 'open', 'changeLink'], array_keys($changed['_links']));
    }

    #[DataProvider('gesturesOnMyPage')]
    public function testNoGestureOnMyPageWithoutSigningIn(string $method, string $uri): void
    {
        $this->requestJson($method, $uri, 'GET' === $method || 'DELETE' === $method ? null : []);

        $this->assertResponseStatusCodeSame(401);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function gesturesOnMyPage(): iterable
    {
        yield 'set the windows' => ['PATCH', '/api/me/booking-page/windows'];
        yield 'add an inactive period' => ['POST', '/api/me/booking-page/inactive-periods'];
        yield 'remove an inactive period' => ['DELETE', '/api/me/booking-page/inactive-periods/0190a1c0-0000-7000-8000-0000000000e1'];
        yield 'offer channels' => ['PATCH', '/api/me/booking-page/channels'];
        yield 'open' => ['POST', '/api/me/booking-page/opening'];
        yield 'close' => ['DELETE', '/api/me/booking-page/opening'];
        yield 'change the link' => ['POST', '/api/me/booking-page/link'];
    }

    public function testAPageWithoutLinkDoesNotChangeIt(): void
    {
        $this->login();

        $problem = $this->requestJson('POST', '/api/me/booking-page/link');

        $this->assertResponseStatusCodeSame(409);
        $this->assertSame('bookingPageWithoutLink', $problem['reason']);
    }

    public function testASignedInMemberReadsAPublicPageToo(): void
    {
        $assistant = $this->bearerOf('assistant-user');
        $code = $this->anOpenPage();

        $this->signInWith($assistant);

        $page = $this->requestJson('GET', '/api/booking-pages/'.$code);
        $this->assertTrue($page['open']);
        $this->assertSame(['call', 'videoCall'], $page['channels']);
    }

    public function testAPublicPageOffersTheFreeSlotsOfTomorrowOnTheClockOfItsHost(): void
    {
        $code = $this->anOpenPage();
        $this->requestJson('PATCH', '/api/me/booking-page/windows', ['timeZone' => 'Europe/Paris', 'windows' => [['weekday' => 'friday', 'from' => '09:00', 'to' => '09:30']]]);

        $this->signOut();
        $page = $this->requestJson('GET', '/api/booking-pages/'.$code);

        $this->assertCount(14, $page['days']);
        $this->assertSame(['day' => '2026-10-16', 'slots' => ['2026-10-16T09:00:00+02:00', '2026-10-16T09:15:00+02:00']], $page['days'][0]);
    }

    public function testAPublicPageSaysWhoItsHostIsAndOnWhichClock(): void
    {
        $code = $this->anOpenPage();
        $this->requestJson('PATCH', '/api/me/booking-page/windows', ['timeZone' => 'Europe/Paris', 'windows' => [['weekday' => 'friday', 'from' => '09:00', 'to' => '09:30']]]);

        $this->signOut();
        $page = $this->requestJson('GET', '/api/booking-pages/'.$code);

        $this->assertSame(['firstName' => 'Camille', 'lastName' => 'Martin', 'picture' => null, 'organization' => 'SOLEAN IT'], $page['host']);
        $this->assertSame('Europe/Paris', $page['timeZone']);
    }

    public function testASlotWhereItsHostPlannedAnExchangeIsNotFree(): void
    {
        $code = $this->anOpenPage();
        $this->requestJson('PATCH', '/api/me/booking-page/windows', ['timeZone' => 'Europe/Paris', 'windows' => [['weekday' => 'friday', 'from' => '09:00', 'to' => '09:30']]]);
        $organization = $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'ACME']])['id'];
        $this->requestJson('POST', '/api/organizations/'.$organization.'/upcoming-exchanges', ['exchange' => ['channel' => 'call', 'occurredAt' => '2026-10-16T07:00:00+00:00', 'durationInMinutes' => 15]]);
        $this->assertResponseIsSuccessful();

        $this->signOut();
        $page = $this->requestJson('GET', '/api/booking-pages/'.$code);

        $this->assertSame(['2026-10-16T09:15:00+02:00'], $page['days'][0]['slots']);
    }

    public function testMyPageIsMineOnly(): void
    {
        $assistant = $this->bearerOf('assistant-user');
        $this->login();
        $this->requestJson('PATCH', '/api/me/booking-page/channels', ['channels' => ['call']]);

        $this->signInWith($assistant);
        $theirs = $this->requestJson('GET', '/api/me/booking-page');

        $this->assertSame([], $theirs['channels']);
    }

    public function testWithoutGoogleConnectedMyPageDoesNotOpen(): void
    {
        $this->login();
        $this->requestJson('PATCH', '/api/me/booking-page/channels', ['channels' => ['call']]);

        $problem = $this->requestJson('POST', '/api/me/booking-page/opening');

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('noConnectedCalendar', $problem['reason']);
    }

    public function testAWindowTooShortIsRefused(): void
    {
        $this->login();

        $problem = $this->requestJson('PATCH', '/api/me/booking-page/windows', ['timeZone' => 'Europe/Paris', 'windows' => [['weekday' => 'monday', 'from' => '14:00', 'to' => '14:10']]]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('windowTooShort', $problem['reason']);
    }

    public function testIAddAnInactivePeriodThenRemoveIt(): void
    {
        $this->login();

        $added = $this->requestJson('POST', '/api/me/booking-page/inactive-periods', ['firstDay' => '2026-12-21', 'lastDay' => '2027-01-04']);
        $this->assertResponseStatusCodeSame(201);
        $period = $added['inactivePeriods'][0];
        $this->assertSame(['2026-12-21', '2027-01-04'], [$period['firstDay'], $period['lastDay']]);
        $this->assertSame('DELETE', $period['_links']['remove']['method']);

        $removed = $this->requestJson($period['_links']['remove']['method'], $period['_links']['remove']['href']);

        $this->assertResponseIsSuccessful();
        $this->assertSame([], $removed['inactivePeriods']);
    }

    public function testAnInactivePeriodIDoNotHaveIsNotFound(): void
    {
        $this->login();

        $problem = $this->requestJson('DELETE', '/api/me/booking-page/inactive-periods/'.self::newIdentity());

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame('unknownInactivePeriod', $problem['reason']);
    }

    public function testAnInactivePeriodEndingBeforeItStartsIsRefused(): void
    {
        $this->login();

        $problem = $this->requestJson('POST', '/api/me/booking-page/inactive-periods', ['firstDay' => '2027-01-04', 'lastDay' => '2026-12-21']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('periodEndsBeforeItStarts', $problem['reason']);
    }

    public function testMyOpenPageIsReadWithoutSigningInAndOffersNothingOnceClosed(): void
    {
        $code = $this->anOpenPage();
        $me = $this->client->getServerParameter('HTTP_AUTHORIZATION');

        $this->signOut();
        $public = $this->requestJson('GET', '/api/booking-pages/'.$code);
        $this->assertResponseIsSuccessful();
        $this->assertTrue($public['open']);

        $this->signInWith($me);
        $closed = $this->requestJson('DELETE', '/api/me/booking-page/opening');
        $this->assertResponseIsSuccessful();
        $this->assertFalse($closed['open']);
        $this->assertSame($code, $closed['code']);

        $this->signOut();
        $this->assertSame(self::NOTHING_SHOWN, $this->requestJson('GET', '/api/booking-pages/'.$code));
    }

    public function testMyFormerLinkShowsNothingOnceChanged(): void
    {
        $former = $this->anOpenPage();

        $changed = $this->requestJson('POST', '/api/me/booking-page/link');
        $this->assertResponseIsSuccessful();
        $this->assertNotSame($former, $changed['code']);

        $this->signOut();
        $this->assertSame(self::NOTHING_SHOWN, $this->requestJson('GET', '/api/booking-pages/'.$former));
        $this->assertResponseIsSuccessful();
        $this->requestJson('GET', '/api/booking-pages/'.$changed['code']);
        $this->assertResponseIsSuccessful();
    }

    public function testALinkNobodyDrewShowsNothingAsAClosedOne(): void
    {
        $page = $this->requestJson('GET', '/api/booking-pages/AbCdEfGhIjKlMnOpQrStUv');

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::NOTHING_SHOWN, $page);
    }

    public function testMyOpenPageIsSuspendedOnceGoogleIsDisconnected(): void
    {
        $this->anOpenPage();
        $this->assertFalse($this->requestJson('GET', '/api/me/booking-page')['suspended']);

        $this->requestJson('DELETE', '/api/me/connections/google');

        $this->assertTrue($this->requestJson('GET', '/api/me/booking-page')['suspended']);
    }

    public function testWithoutGoogleConnectedAPublicPageShowsNothing(): void
    {
        $code = $this->anOpenPage();
        $this->requestJson('DELETE', '/api/me/connections/google');

        $this->signOut();

        $this->assertSame(self::NOTHING_SHOWN, $this->requestJson('GET', '/api/booking-pages/'.$code));
    }

    public function testMyPageIsNotReadWithoutSigningIn(): void
    {
        $this->requestJson('GET', '/api/me/booking-page');

        $this->assertResponseStatusCodeSame(401);
    }

    private function anOpenPage(): string
    {
        $this->login();
        $this->connectGoogle();
        $this->requestJson('PATCH', '/api/me/booking-page/channels', ['channels' => ['call', 'videoCall']]);

        return (string) $this->requestJson('POST', '/api/me/booking-page/opening')['code'];
    }

    private function signOut(): void
    {
        $this->signInWith('');
    }

    /**
     * The kernel kept between the requests of a test (the double of Google keeps what it granted), the clock checking
     * the tokens stays at its first request: a member signs in again with a token taken before it, never a new one,
     * whose moment of issue would come after that clock.
     */
    private function signInWith(string $authorization): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', $authorization);
    }

    /**
     * The token of a member, taken before the first request of the test.
     */
    private function bearerOf(string $reference): string
    {
        $this->login($reference);

        return (string) $this->client->getServerParameter('HTTP_AUTHORIZATION');
    }

    private function connectGoogle(): void
    {
        parse_str((string) parse_url($this->requestJson('POST', '/api/me/connections/google/authorizations')['address'], \PHP_URL_QUERY), $query);
        self::getContainer()->get(GoogleDouble::class)->grants(self::CODE_OF_GOOGLE, 'michel@exemple.fr', 'a-refresh-token-'.$query['state']);
        $this->requestJson('POST', '/api/me/connections/google', ['code' => self::CODE_OF_GOOGLE, 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }
}
