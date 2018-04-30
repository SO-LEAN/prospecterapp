<?php

namespace Tests\App\Controller;

use Tests\App\Base\ControllerTest;

class OrganizationControllerTest extends ControllerTest
{

    public function provideUri(): array
    {
        return [
            '/organization/add' => ['/organization/add'],
            '/organization/view' => ['/organization/view/1'],
        ];
    }

    public function testSubmitCreateOrganizationRequest() : void
    {
        $this->login();

        $form['create_organization_form[corporateName]'] = 'corporate name';
        $form['create_organization_form[email]'] = 'email@test.com';

        $this->submitForm($form, '/organization/add', 'Create');
        $this->assertRegExp('#/organization/view/[0-9]+$#', $this->client->getResponse()->headers->get('location'));
    }
}
