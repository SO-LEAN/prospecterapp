<?php

namespace Tests\App\Controller\Web;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ControllerTestCase;

#[Group('web')]
class SecurityControllerTest extends ControllerTestCase
{
    #[DataProvider('provideCredentials')]
    public function testNotifyUserOnErrors(string $message, string $userName, string $password, ?string $csrf): void
    {
        $this->submitCredentials($userName, $password, $csrf);
        $crawler = $this->client->followRedirect();

        $this->assertSame($message, $this->grab($crawler, 'login-error'));
    }

    /**
     * @return array<string, array{string, string, string, ?string}>
     */
    public static function provideCredentials(): array
    {
        return [
            'prospector/badP@$$word' => ['Bad credentials', 'prospector', 'badP@$$word', null],
            'prospector/password/bad_token' => ['Invalid CSRF token.', 'prospector', 'password', 'bad_token'],
        ];
    }

    public function testRedirectToDashboardAfterLoginSuccess(): void
    {
        $this->submitCredentials('prospector', 'password');

        $this->assertResponseRedirects('/dashboard/view');
    }

    public function testRedirectToLoginPageAfterLogout(): void
    {
        $this->login();

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('http://api.test.prospecter.io/login');
    }

    private function submitCredentials(string $username, string $password, ?string $csrf = null): void
    {
        $crawler = $this->client->request('GET', '/login');
        $form = $crawler->selectButton('Login')->form();

        $form['login[userName]'] = $username;
        $form['login[password]'] = $password;

        if ($csrf) {
            $form['login[_token]'] = $csrf;
        }

        $this->client->submit($form);
    }
}
