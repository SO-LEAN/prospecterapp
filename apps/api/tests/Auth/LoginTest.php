<?php

namespace Tests\App\Auth;

use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ControllerTestCase;

#[Group('auth')]
class LoginTest extends ControllerTestCase
{
    public function testSignInResumesTheAuthorizationRequest(): void
    {
        $authorize = '/authorize?response_type=code&client_id=prospecter-front';
        $this->client->request('GET', $authorize);
        $this->assertResponseRedirects('http://api.test.prospecter.io/login');

        $this->submitCredentials('prospector', 'password');

        // Symfony normalizes the saved URL (sorted query parameters)
        $this->assertResponseRedirects('http://api.test.prospecter.io/authorize?client_id=prospecter-front&response_type=code');
    }

    public function testSignInWithoutPendingAuthorizationGoesToTheSpa(): void
    {
        $this->submitCredentials('prospector', 'password');

        $this->assertResponseRedirects('http://test.prospecter.io');
    }

    public function testWrongPasswordIsReported(): void
    {
        $this->submitCredentials('prospector', 'wrong');
        $crawler = $this->client->followRedirect();

        $this->assertSame('Identifiant ou mot de passe incorrect.', $this->grab($crawler, 'login-error'));
        $this->assertSame('prospector', $crawler->filter('#userName')->attr('value'));
    }

    public function testLogoutGoesBackToTheSpa(): void
    {
        $this->login();

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('http://test.prospecter.io');
    }

    private function submitCredentials(string $userName, string $password): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->client->submit($crawler->selectButton('Se connecter')->form(), [
            'login[userName]' => $userName,
            'login[password]' => $password,
        ]);
    }
}
