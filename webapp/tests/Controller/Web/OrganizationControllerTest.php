<?php

namespace Tests\App\Controller\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ControllerTestCase;

#[Group('web')]
class OrganizationControllerTest extends ControllerTestCase
{
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
            '/organizations' => ['/organizations'],
            '/organizations/add' => ['/organizations/add'],
            '/organizations/{id}/view' => ['/organizations/1/view'],
            '/organizations/{id}/update' => ['/organizations/1/update'],
            '/user-account/update' => ['/user-account/update'],
        ];
    }

    public function testSubmitCreateOrganizationRequest(): void
    {
        $this->login();

        $this->submitForm([
            'create_organization[corporateName]' => 'corporate name',
            'create_organization[email]' => 'email@test.com',
        ], '/organizations/add', 'Create');

        $this->assertMatchesRegularExpression('#/organizations/[0-9]+/view$#', $this->client->getResponse()->headers->get('location'));
    }
}
