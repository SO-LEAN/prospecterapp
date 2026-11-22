<?php

declare(strict_types=1);

namespace Tests\App\SignIn;

use App\Shared\Typed;
use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\DeliversThroughTheOutbox;

final class OpenAccountCommandTest extends ApiTestCase
{
    use DeliversThroughTheOutbox;

    private const string FOUNDER = 'founder@babbage.test';
    private const string PASSWORD = 'analytical-engine';

    protected function tearDown(): void
    {
        $this->deliverAsTheTestsDo();
        parent::tearDown();
    }

    public function testTheFounderSignsInToTheNewAccountAndProspects(): void
    {
        $this->assertSame(Command::SUCCESS, $this->open('Babbage & Co', self::FOUNDER, '--form=SA'));

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$this->accessTokenOfTheFounder());
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $legalIdentity = Typed::document(Typed::document($account['organization'])['legalIdentity']);
        $this->assertSame('Babbage & Co', $legalIdentity['corporateName']);
        $this->assertSame('SA', $legalIdentity['form']);
        $this->assertSame(['firstName' => null, 'lastName' => null], $account['name'], 'the founder fills in their profile');
        $this->assertNull(Typed::document($account['contactDetails'])['email']);

        $this->requestJson('GET', '/api/organizations');
        $this->assertResponseIsSuccessful('the founder prospects');
    }

    public function testTheGestureCommitsInAccountsAloneThroughTheOutbox(): void
    {
        $this->deliverThroughTheOutbox();

        $this->assertSame(Command::SUCCESS, $this->open('Babbage & Co', self::FOUNDER));

        $this->assertSame([], $this->outboxOf('personal_data'));
        $this->assertCount(1, $this->outboxOf('accounts'));
        $this->assertNotNull(self::service(Credentials::class)->memberSigningInWith(self::FOUNDER));
    }

    public function testAnEmailSomebodySignsInWithOpensNothing(): void
    {
        $this->assertSame(Command::FAILURE, $this->open('Babbage & Co', 'prospector@solean-it.io'));
    }

    public function testAShortPasswordOpensNothing(): void
    {
        $this->assertSame(Command::INVALID, $this->open('Babbage & Co', self::FOUNDER, '--password=short'));
        $this->assertNull(self::service(Credentials::class)->memberSigningInWith(self::FOUNDER));
    }

    private function open(string $corporateName, string $email, string ...$options): int
    {
        $command = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('app:account:open'));
        $input = ['corporate-name' => $corporateName, 'email' => $email, '--password' => self::PASSWORD];

        foreach ($options as $option) {
            [$name, $value] = explode('=', $option, 2);
            $input[$name] = $value;
        }

        return $command->execute($input);
    }

    private function accessTokenOfTheFounder(): string
    {
        $pool = self::service(UserPool::class);
        $credentials = self::service(Credentials::class);

        $result = Typed::document(self::service(CognitoIdentityProviderClient::class)->adminInitiateAuth([
            'UserPoolId' => $pool->id(),
            'ClientId' => $pool->clientId(),
            'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
            'AuthParameters' => ['USERNAME' => Typed::string($credentials->memberSigningInWith(self::FOUNDER)), 'PASSWORD' => self::PASSWORD],
        ])['AuthenticationResult']);

        return Typed::string($result['AccessToken']);
    }
}
