<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Tests\App\Base\ActsAsItsEndpoint;
use Tests\App\Base\ApiTestCase;
use Tests\App\Double\GoogleDouble;

/**
 * An AI sets the booking page of its member, opens and closes it, as its member does on the screens (ADR 0163):
 * every member, each on their own page.
 */
#[Group('api')]
class BookingPageToolsApiTest extends ApiTestCase
{
    use ActsAsItsEndpoint;

    private const array AFTERNOONS = ['timeZone' => 'Europe/Paris', 'windows' => [['weekday' => 'monday', 'from' => '14:00', 'to' => '17:00']]];

    private const array HOLIDAYS = ['firstDay' => '2026-12-21', 'lastDay' => '2027-01-04'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->aMemberOfTheFixtures('admin-user', Role::User, Role::Admin);
        $this->aMemberOfTheFixtures('member-user', Role::User);
        $this->aMemberOfAnotherAccount('stranger');
    }

    public function testTheAiSetsTheWeeklyWindowsOfThePage(): void
    {
        $page = $this->assertTheToolActsAsItsEndpoint('set-booking-slots', self::AFTERNOONS, 'PATCH', '/api/me/booking-page/windows', self::AFTERNOONS, self::WRITERS);

        $this->assertSame([self::AFTERNOONS['windows'], 'Europe/Paris'], [$page['windows'], $page['timeZone']]);
    }

    public function testTheAiOffersTheChannelsOfThePage(): void
    {
        $page = $this->assertTheToolActsAsItsEndpoint('set-booking-channels', ['channels' => ['call', 'videoCall']], 'PATCH', '/api/me/booking-page/channels', ['channels' => ['call', 'videoCall']], self::WRITERS);

        $this->assertSame(['call', 'videoCall'], $page['channels']);
    }

    public function testTheAiOpensThePageOfAMemberWhoseCalendarIsConnected(): void
    {
        $this->aPageReadyToOpen();

        $page = $this->assertTheToolActsAsItsEndpoint('open-booking', [], 'POST', '/api/me/booking-page/opening', null, self::WRITERS);

        $this->assertTrue($page['open']);
        $this->assertNotNull($page['code']);
    }

    public function testTheAiClosesThePageOnceItsMemberConfirmed(): void
    {
        $this->anOpenPage();

        $page = $this->assertTheToolActsAsItsEndpoint('close-booking', [], 'DELETE', '/api/me/booking-page/opening', null, self::WRITERS);

        $this->assertFalse($page['open']);
        $this->assertTheToolAsksForAConfirmation('close-booking');
    }

    public function testTheAiChangesThePublicLinkOfThePage(): void
    {
        $former = $this->anOpenPage();

        $page = $this->assertTheToolActsAsItsEndpoint('set-booking-link', [], 'POST', '/api/me/booking-page/link', null, self::WRITERS);

        $this->assertNotSame($former, $page['code']);
    }

    public function testTheAiAddsAnInactivePeriodToThePage(): void
    {
        $page = $this->assertTheToolActsAsItsEndpoint('add-inactive-period', self::HOLIDAYS, 'POST', '/api/me/booking-page/inactive-periods', self::HOLIDAYS, self::WRITERS);

        $this->assertSame([['2026-12-21', '2027-01-04']], array_map(static fn (array $period): array => [$period['firstDay'], $period['lastDay']], $page['inactivePeriods']));
    }

    public function testTheAiRemovesAnInactivePeriodOnceItsMemberConfirmed(): void
    {
        $this->login();
        $period = $this->requestJson('POST', '/api/me/booking-page/inactive-periods', self::HOLIDAYS)['inactivePeriods'][0]['id'];

        $page = $this->assertTheToolActsAsItsEndpoint('remove-inactive-period', ['id' => $period], 'DELETE', '/api/me/booking-page/inactive-periods/'.$period, null, ['prospector-user']);

        $this->assertSame([], $page['inactivePeriods']);
        $this->assertTheToolAsksForAConfirmation('remove-inactive-period');
    }

    /**
     * The page of the prospector open at its public link.
     *
     * @return string the code of its link
     */
    private function anOpenPage(): string
    {
        $this->aPageReadyToOpen();
        $page = $this->requestJson('POST', '/api/me/booking-page/opening');
        $this->assertResponseIsSuccessful();

        return $page['code'];
    }

    /**
     * The page of the prospector offering video calls, their calendar connected.
     */
    private function aPageReadyToOpen(): void
    {
        $this->keepTheKernel();
        $this->login();
        $this->connectGoogle();
        $this->requestJson('PATCH', '/api/me/booking-page/channels', ['channels' => ['videoCall']]);
    }

    /**
     * The calendar of the member signed in at Google connected, its consent given.
     */
    private function connectGoogle(): void
    {
        parse_str((string) parse_url($this->requestJson('POST', '/api/me/connections/google/authorizations')['address'], \PHP_URL_QUERY), $query);
        self::getContainer()->get(GoogleDouble::class)->grants('a-code-of-google', 'michel@exemple.fr', 'a-refresh-token-'.$query['state']);
        $this->requestJson('POST', '/api/me/connections/google', ['code' => 'a-code-of-google', 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }
}
