<?php

namespace Tests\App\Controller\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ControllerTestCase;

#[Group('web')]
class ProspectControllerTest extends ControllerTestCase
{
    public function testRedirectToLoginPageWhenDisplayDashboardIfNotAuthenticated(): void
    {
        $this->client->request('GET', '/dashboard/view');

        $this->assertResponseRedirects('http://test.prospecter.io/login');
    }

    #[DataProvider('provideUri')]
    public function testRouteIsReachedIfAuthenticatedAsProspector(string $uri): void
    {
        $this->login();

        $crawler = $this->client->request('GET', $uri);

        $this->assertResponseIsSuccessful();
        $this->assertSame('prospector', $this->grab($crawler, 'logged-user'));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideUri(): array
    {
        return [
            '/dashboard/view' => ['/dashboard/view'],
            '/prospects/add' => ['/prospects/add'],
        ];
    }
}
