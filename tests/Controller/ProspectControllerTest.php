<?php

namespace Tests\App\Controller;

use App\Tests\Base\ControllerTest;

class ProspectControllerTest extends ControllerTest
{
    public function testRedirectToLoginPageWhenDisplayDashboardIfNotAuthenticated() : void
    {
        $this->client = $this->createClient();

        $this->client->request('GET', '/dashboard/view');

        $this->assertStatusCode(302, $this->client);
        $this->assertEquals('/login', $this->client->getResponse()->headers->get('location'));
    }

    /**
     * @param string $uri
     * @dataProvider provideUri
     */
    public function testRouteIsReachedIfAuthenticatedAsProspector(string $uri) : void
    {
        $this->login();

        $crawler = $this->client->request('GET', $uri);

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertEquals('prospector', $this->grab($crawler, 'logged-user'));
    }

    public function provideUri(): array
    {
        return [
            '/dashboard/view' => ['/dashboard/view'],
            '/prospect/add' => ['/prospect/add'],
        ];
    }

}
