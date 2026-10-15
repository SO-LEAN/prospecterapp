<?php

namespace App\Tests;

class LoginTest extends AuthTestCase
{
    public function testSignInResumesTheAuthorizationRequest(): void
    {
        $authorize = '/authorize?response_type=code&client_id=prospecter-front';
        $this->client->request('GET', $authorize);
        $this->assertResponseRedirects('http://api.test.prospecter.io/login');

        $this->submitCredentials('prospector@solean-it.io', 'password');

        $authorizeWithSortedParameters = 'http://api.test.prospecter.io/authorize?client_id=prospecter-front&response_type=code';
        $this->assertResponseRedirects($authorizeWithSortedParameters);
    }

    public function testSignInWithoutPendingAuthorizationGoesToTheSpa(): void
    {
        $this->submitCredentials('prospector@solean-it.io', 'password');

        $this->assertResponseRedirects('http://test.prospecter.io');
    }

    public function testWrongPasswordIsReported(): void
    {
        $this->submitCredentials('prospector@solean-it.io', 'wrong');
        $crawler = $this->client->followRedirect();

        $this->assertSame('E-mail ou mot de passe incorrect.', $this->grab($crawler, 'login-error'));
        $this->assertSame('prospector@solean-it.io', $crawler->filter('#email')->attr('value'));
    }

    public function testLogoutGoesBackToTheSpa(): void
    {
        $this->signIn();

        $this->client->request('GET', '/logout');

        $this->assertResponseRedirects('http://test.prospecter.io');
    }

    private function submitCredentials(string $loginEmail, string $password): void
    {
        $crawler = $this->client->request('GET', '/login');

        $this->client->submit($crawler->selectButton('Se connecter')->form(), [
            'login[email]' => $loginEmail,
            'login[password]' => $password,
        ]);
    }
}
