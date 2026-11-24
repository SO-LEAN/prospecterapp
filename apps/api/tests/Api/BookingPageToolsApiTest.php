<?php

declare(strict_types=1);

namespace Tests\App\Api;

use App\Shared\Typed;
use PHPUnit\Framework\Attributes\Group;
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

    public function testTheAiSetsTheWeeklyWindowsOfThePage(): void
    {
        $page = $this->assertTheToolActsAsItsEndpoint('set-booking-slots', self::AFTERNOONS);

        $this->assertSame([self::AFTERNOONS['windows'], 'Europe/Paris'], [$page['windows'], $page['timeZone']]);
    }

    public function testTheAiOffersTheChannelsOfThePage(): void
    {
        $page = $this->assertTheToolActsAsItsEndpoint('set-booking-channels', ['channels' => ['call', 'videoCall']]);

        $this->assertSame(['call', 'videoCall'], $page['channels']);
    }

    public function testTheAiOpensThePageOfAMemberWhoseCalendarIsConnected(): void
    {
        $this->aPageReadyToOpen();

        $page = $this->assertTheToolActsAsItsEndpoint('open-booking', []);

        $this->assertTrue($page['open']);
        $this->assertNotNull($page['code']);
    }

    public function testTheAiClosesThePage(): void
    {
        $this->anOpenPage();

        $page = $this->assertTheToolActsAsItsEndpoint('close-booking', []);

        $this->assertFalse($page['open']);
    }

    public function testTheAiChangesThePublicLinkOfThePage(): void
    {
        $former = $this->anOpenPage();

        $page = $this->assertTheToolActsAsItsEndpoint('set-booking-link', []);

        $this->assertNotSame($former, $page['code']);
    }

    public function testTheAiAddsAnInactivePeriodToThePage(): void
    {
        $page = $this->assertTheToolActsAsItsEndpoint('add-inactive-period', self::HOLIDAYS);

        $this->assertSame([['2026-12-21', '2027-01-04']], array_map(static fn (array $period): array => [$period['firstDay'], $period['lastDay']], self::recordsAt($page, 'inactivePeriods')));
    }

    public function testTheAiRemovesAnInactivePeriod(): void
    {
        $this->login();
        $period = self::stringAt($this->requestJson('POST', '/api/me/booking-page/inactive-periods', self::HOLIDAYS), 'inactivePeriods', 0, 'id');

        $page = $this->assertTheToolActsAsItsEndpoint('remove-inactive-period', ['id' => $period]);

        $this->assertSame([], $page['inactivePeriods']);
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

        return self::stringAt($page, 'code');
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
        parse_str((string) parse_url(self::stringAt($this->requestJson('POST', '/api/me/connections/google/authorizations'), 'address'), \PHP_URL_QUERY), $query);
        self::service(GoogleDouble::class)->grants('a-code-of-google', 'michel@exemple.fr', 'a-refresh-token-'.Typed::string($query['state']));
        $this->requestJson('POST', '/api/me/connections/google', ['code' => 'a-code-of-google', 'state' => $query['state']]);
        $this->assertResponseStatusCodeSame(201);
    }
}
