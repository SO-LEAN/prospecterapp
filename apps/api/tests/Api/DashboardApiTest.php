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

        $dashboard = $this->requestJson('GET', '/api/dashboard');

        $this->assertResponseIsSuccessful();
        $this->assertSame('SOLEAN IT', $dashboard['organizationName']);
        $this->assertArrayHasKey('activeOrganizations', $dashboard['stats']);
        $this->assertSame([], $dashboard['markers'], 'organizations without coordinates are not placed on the map');
    }
}
