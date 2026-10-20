<?php

namespace Tests\App\SignIn;

use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;

final class RegisterMemberCommandTest extends ApiTestCase
{
    private const string FOUNDER = 'founder@babbage.test';
    private const string MEMBER = 'ada@babbage.test';
    private const string PASSWORD = 'analytical-engine';

    protected function setUp(): void
    {
        parent::setUp();
        $command = new CommandTester(new Application(self::$kernel)->find('app:account:open'));
        $command->execute(['corporate-name' => 'Babbage & Co', 'email' => self::FOUNDER, '--password' => self::PASSWORD]);
    }

    public function testTheMemberSignsInToTheAccountOfTheAdministratorUnderTheirOwnProfile(): void
    {
        $this->assertSame(Command::SUCCESS, $this->register(self::FOUNDER, self::MEMBER, '--first-name=Ada', '--last-name=Lovelace'));

        $this->signInAs(self::MEMBER);
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertSame('Babbage & Co', $account['organization']['legalIdentity']['corporateName']);
        $this->assertSame(['firstName' => 'Ada', 'lastName' => 'Lovelace'], $account['name']);
        $this->assertSame(self::MEMBER, $account['contactDetails']['email']);

        $this->signInAs(self::FOUNDER);
        $this->assertSame(self::FOUNDER, $this->requestJson('GET', '/api/me')['contactDetails']['email'], 'the profile of the founder is left alone');
    }

    public function testAProspectorRegisteredWithoutOptionsHasNoRightToPersonalData(): void
    {
        $this->register(self::FOUNDER, self::MEMBER);

        $this->signInAs(self::MEMBER);
        $this->requestJson('GET', '/api/organizations');
        $this->assertResponseIsSuccessful('the member prospects');
        $this->requestJson('GET', '/api/contacts?q=ada');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTheOptionGivesTheRightToPersonalData(): void
    {
        $this->register(self::FOUNDER, self::MEMBER, '--personal-data');

        $this->signInAs(self::MEMBER);
        $this->requestJson('GET', '/api/contacts?q=ada');
        $this->assertResponseIsSuccessful();
    }

    public function testOnlyAnAdministratorRegistersAMember(): void
    {
        $this->assertSame(Command::FAILURE, $this->register('prospector@solean-it.io', self::MEMBER));
        $this->assertNull($this->credentials()->memberSigningInWith(self::MEMBER));
    }

    public function testAnEmailSomebodySignsInWithRegistersNobody(): void
    {
        $this->assertSame(Command::FAILURE, $this->register(self::FOUNDER, 'assistant@solean-it.io'));
    }

    public function testAnAdministratorNobodySignsInWithRegistersNobody(): void
    {
        $this->assertSame(Command::FAILURE, $this->register('nobody@babbage.test', self::MEMBER));
    }

    public function testAShortPasswordRegistersNobody(): void
    {
        $this->assertSame(Command::INVALID, $this->register(self::FOUNDER, self::MEMBER, '--password=short'));
        $this->assertNull($this->credentials()->memberSigningInWith(self::MEMBER));
    }

    private function register(string $administratorEmail, string $email, string ...$options): int
    {
        $command = new CommandTester(new Application(self::$kernel)->find('app:member:register'));
        $input = ['administrator-email' => $administratorEmail, 'email' => $email, '--password' => self::PASSWORD];

        foreach ($options as $option) {
            [$name, $value] = explode('=', $option, 2) + [1 => true];
            $input[$name] = $value;
        }

        return $command->execute($input);
    }

    private function signInAs(string $email): void
    {
        $pool = self::getContainer()->get(UserPool::class);
        $tokens = self::getContainer()->get(CognitoIdentityProviderClient::class)->adminInitiateAuth([
            'UserPoolId' => $pool->id(),
            'ClientId' => $pool->clientId(),
            'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
            'AuthParameters' => ['USERNAME' => $this->credentials()->memberSigningInWith($email), 'PASSWORD' => self::PASSWORD],
        ])['AuthenticationResult'];

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$tokens['AccessToken']);
    }

    private function credentials(): Credentials
    {
        return self::getContainer()->get(Credentials::class);
    }
}
