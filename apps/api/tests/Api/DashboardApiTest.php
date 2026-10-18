<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

#[Group('api')]
class DashboardApiTest extends ApiTestCase
{
    public function testDashboard(): void
    {
        $this->login();
        $this->requestJson('POST', '/api/organizations', ['legalIdentity' => ['corporateName' => 'Nowhere Ltd']]);

        $dashboard = $this->requestJson('GET', '/api/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSame('SOLEAN IT', $dashboard['organizationName']);
        $this->assertArrayHasKey('activeOrganizations', $dashboard['stats']);
        $this->assertSame(['Babbage & Co SAS'], array_column($dashboard['markers'], 'name'), 'only the located organizations are placed on the map');
    }
}
