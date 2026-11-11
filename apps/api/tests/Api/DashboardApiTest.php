<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFigures;
use Prospecter\Core\Reporting\Application\RebuildFigures\RebuildFiguresHandler;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Tests\App\Base\ApiTestCase;

#[Group('api')]
class DashboardApiTest extends ApiTestCase
{
    private const array FIGURES = ['activeOrganizations', 'locatedOrganizations', 'contacts', 'exchanges', 'exchangesThisMonth'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        self::getContainer()->get(RebuildFiguresHandler::class)(new RebuildFigures());
        $this->login();
    }

    public function testMyDashboardNamesMyOrganizationAndPlacesTheLocatedOnesOnTheMap(): void
    {
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Nowhere Ltd']]);

        $dashboard = $this->requestJson('GET', '/api/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSame('SOLEAN IT', $dashboard['organizationName']);
        $this->assertSame(['Babbage & Co SAS'], array_column($dashboard['markers'], 'name'), 'only the located organizations are placed on the map');
    }

    public function testTheOrganizationsStartedAreCounted(): void
    {
        $before = $this->requestJson('GET', '/api/dashboard')['stats'];

        $this->startProspectingAnOrganizationTheGeocoderPlaces();
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Nowhere Ltd']]);
        $after = $this->requestJson('GET', '/api/dashboard')['stats'];

        $this->assertSame(self::FIGURES, array_keys($after));
        $this->assertSame($before['activeOrganizations'] + 2, $after['activeOrganizations']);
        $this->assertSame($before['locatedOrganizations'] + 1, $after['locatedOrganizations']);
    }

    private function startProspectingAnOrganizationTheGeocoderPlaces(): void
    {
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory(new JsonMockResponse([
            'type' => 'FeatureCollection',
            'features' => [[
                'type' => 'Feature',
                'geometry' => ['type' => 'Point', 'coordinates' => [7.7456, 48.5834]],
                'properties' => ['label' => 'Strasbourg', 'score' => 0.9],
            ]],
        ]));
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Somewhere SAS'], 'address' => ['city' => 'Strasbourg']]);
        self::getContainer()->get(MockHttpClient::class)->setResponseFactory(null);
    }
}
