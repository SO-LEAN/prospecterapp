<?php

namespace Tests\App\Controller;

use Tests\App\Base\ControllerTest;

class SecurityControllerTest extends ControllerTest
{
    /**
     * @param string $message
     * @param string $userName
     * @param string $password
     * @param string $csrf
     *
     * @dataProvider provideCredentials
     */
    public function testNotifyUserOnErrors(string $message, string $userName, string $password, ?string $csrf) : void
    {
        $this->submitCredentials($userName, $password, $csrf);
        $crawler = $this->client->followRedirect();

        $this->assertEquals($message, $this->grab($crawler, 'login-error'));
    }

    /**
     * @return array
     */
    public function provideCredentials(): array
    {
        return [
            'prospector/badP@$$word' => ['Bad credentials', 'prospector', 'badP@$$word', null],
            'prospector/password/bad_token' => ['Invalid CSRF token.', 'prospector', 'password', 'bad_token'],
        ];
    }

    public function testRedirectToDashboardAfterLoginSuccess() : void
    {
        $this->submitCredentials('prospector', 'password');

        $this->assertStatusCode(302, $this->client);
        $this->assertEquals('/dashboard/view', $this->client->getResponse()->headers->get('location'));
    }

    public function testRedirectToLoginPageAfterLogout() : void
    {
        $this->login();

        $this->client->request('GET', '/logout');

        $this->assertStatusCode(302, $this->client);
        $this->assertEquals('http://localhost/login', $this->client->getResponse()->headers->get('location'));
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $csrf
     */
    private function submitCredentials(string $username, string $password, string $csrf = null) : void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form();

        $form['login_form[userName]'] = $username;
        $form['login_form[password]'] = $password;

        if ($csrf) {
            $form['login_form[_token]'] = $csrf;
        }

        $this->client->submit($form);
    }
}
