<?php

namespace Tests\App\Controller;

use Tests\App\Base\ControllerTest;

class OrganizationControllerTest extends ControllerTest
{

    public function provideUri(): array
    {
        return [
            '/organizations/add' => ['/organizations/add'],
            '/organizations/{id}/view' => ['/organizations/1/view'],
        ];
    }

    public function testSubmitCreateOrganizationRequest() : void
    {
        $this->login();

        $form['create_organization_form[corporateName]'] = 'corporate name';
        $form['create_organization_form[email]'] = 'email@test.com';

        $this->submitForm($form, '/organizations/add', 'Create');
        $this->assertRegExp('#/organizations/[0-9]+/view$#', $this->client->getResponse()->headers->get('location'));
    }
}
