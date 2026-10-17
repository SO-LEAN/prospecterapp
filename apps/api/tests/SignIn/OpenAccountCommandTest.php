<?php

namespace Tests\App\SignIn;

use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

final class OpenAccountCommandTest extends ApiTestCase
{
    private const string FOUNDER = 'founder@babbage.test';
    private const string PASSWORD = 'analytical-engine';

    public function testTheFounderSignsInToTheNewAccountAndProspects(): void
    {
        $this->assertSame(Command::SUCCESS, $this->open('Babbage & Co', self::FOUNDER, '--form=SA'));

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$this->tokensOfTheFounder()['AccessToken']);
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Babbage & Co', $account['organization']['legalIdentity']['corporateName']);
        $this->assertSame('SA', $account['organization']['legalIdentity']['form']);
        $this->assertSame(self::FOUNDER, $account['contactDetails']['email']);

        $this->requestJson('GET', '/api/organizations');
        $this->assertResponseIsSuccessful('the founder prospects');
    }

    public function testAnEmailSomebodySignsInWithOpensNothing(): void
    {
        $this->assertSame(Command::FAILURE, $this->open('Babbage & Co', 'prospector@solean-it.io'));
    }

    public function testAShortPasswordOpensNothing(): void
    {
        $this->assertSame(Command::INVALID, $this->open('Babbage & Co', self::FOUNDER, '--password=short'));
        $this->assertNull(self::getContainer()->get(Credentials::class)->memberSigningInWith(self::FOUNDER));
    }

    private function open(string $corporateName, string $email, string ...$options): int
    {
        $command = new CommandTester(new Application(self::$kernel)->find('app:account:open'));
        $input = ['corporate-name' => $corporateName, 'email' => $email, '--password' => self::PASSWORD];

        foreach ($options as $option) {
            [$name, $value] = explode('=', $option, 2);
            $input[$name] = $value;
        }

        return $command->execute($input);
    }

    /**
     * @return array{AccessToken: string}
     */
    private function tokensOfTheFounder(): array
    {
        $pool = self::getContainer()->get(UserPool::class);
        $credentials = self::getContainer()->get(Credentials::class);

        return self::getContainer()->get(CognitoIdentityProviderClient::class)->adminInitiateAuth([
            'UserPoolId' => $pool->id(),
            'ClientId' => $pool->clientId(),
            'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
            'AuthParameters' => ['USERNAME' => $credentials->memberSigningInWith(self::FOUNDER), 'PASSWORD' => self::PASSWORD],
        ])['AuthenticationResult'];
    }
}
