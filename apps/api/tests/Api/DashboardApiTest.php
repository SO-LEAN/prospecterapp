<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;
use Tests\App\Double\OutsideServices;

#[Group('api')]
class DashboardApiTest extends ApiTestCase
{
    use ReadsResources;

    private const array FIGURES = ['activeOrganizations', 'locatedOrganizations', 'contacts', 'exchanges', 'exchangesThisMonth'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->login();
    }

    public function testTheFiguresAreThoseReportingKeepsNotTheOrganizationsTheMapReceives(): void
    {
        $dashboard = $this->requestJson('GET', '/api/dashboard');

        $this->assertNotEmpty($dashboard['markers']);
        $this->assertSame(array_fill_keys(self::FIGURES, 0), $dashboard['stats'], 'nothing counted before the first rebuild of the figures');
    }

    public function testMyDashboardNamesMyOrganizationAndPlacesTheLocatedOnesOnTheMap(): void
    {
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Nowhere Ltd']]);

        $dashboard = $this->requestJson('GET', '/api/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSame('SOLEAN IT', $dashboard['organizationName']);
        $this->assertSame(['Babbage & Co SAS'], array_column(self::recordsAt($dashboard, 'markers'), 'name'), 'only the located organizations are placed on the map');
    }

    public function testTheOrganizationsStartedAreCounted(): void
    {
        self::service(RebuildFiguresHandler::class)(new RebuildFigures());
        $before = self::recordAt($this->requestJson('GET', '/api/dashboard'), 'stats');

        $this->startProspectingAnOrganizationTheGeocoderPlaces();
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Nowhere Ltd']]);
        $after = self::recordAt($this->requestJson('GET', '/api/dashboard'), 'stats');

        $this->assertSame(self::FIGURES, array_keys($after));
        $this->assertSame(self::intAt($before, 'activeOrganizations') + 2, $after['activeOrganizations']);
        $this->assertSame(self::intAt($before, 'locatedOrganizations') + 1, $after['locatedOrganizations']);
    }

    private function startProspectingAnOrganizationTheGeocoderPlaces(): void
    {
        self::service(OutsideServices::class)->geocoderPlaces(7.7456, 48.5834);
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Somewhere SAS'], 'address' => ['city' => 'Strasbourg']]);
        self::service(OutsideServices::class)->reset();
    }
}
