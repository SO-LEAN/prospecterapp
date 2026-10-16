<?php

namespace Tests\App\Api;

use App\Demo\DemoData;
use App\SignIn\Credentials;
use App\SignIn\UserPool;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use PHPUnit\Framework\Attributes\Group;
use Tests\App\Base\ApiTestCase;

/**
 * Where the members sign in, and what a member signs in with, changed with an access token of its own; the
 * user pool of MiniStack checks it for real (ADR 0052).
 */
#[Group('api')]
class SignInTest extends ApiTestCase
{
    public function testTheSpaFindsWhereToSignInBeforeAnyoneIs(): void
    {
        $userPool = $this->requestJson('GET', '/api/user-pool');

        $this->assertResponseIsSuccessful();
        $this->assertSame(self::getContainer()->get(UserPool::class)->id(), $userPool['userPoolId']);
        $this->assertSame(self::getContainer()->get(UserPool::class)->clientId(), $userPool['clientId']);
        $this->assertSame('eu-west-3', $userPool['region']);
    }

    public function testILinkToTheChangesOfWhatISignInWith(): void
    {
        $this->login();

        $signIn = $this->requestJson('GET', '/api/me/sign-in');

        $this->assertSame('prospector@solean-it.io', $signIn['loginEmail']);
        $this->assertSame(['href' => '/api/me/sign-in/login-email', 'method' => 'PATCH'], $signIn['_links']['changeMyLoginEmail']);
        $this->assertSame(['href' => '/api/me/sign-in/password', 'method' => 'PATCH'], $signIn['_links']['changeMyPassword']);
    }

    public function testIChangeMyLoginEmailAndSignInWithIt(): void
    {
        $this->login();

        $this->assertSame('ada@solean-it.io', $this->requestJson('PATCH', '/api/me/sign-in/login-email', ['email' => 'ada@solean-it.io'])['loginEmail']);
        $this->assertSame(DemoData::PROSPECTOR, self::getContainer()->get(Credentials::class)->memberSigningInWith('ada@solean-it.io'));
        $this->assertNull(self::getContainer()->get(Credentials::class)->memberSigningInWith('prospector@solean-it.io'));
    }

    public function testALoginEmailIsAValidEmail(): void
    {
        $this->login();

        $problem = $this->requestJson('PATCH', '/api/me/sign-in/login-email', ['email' => 'not an email']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('email', $problem['violations'][0]['propertyPath']);
    }

    public function testALoginEmailIsUniqueOnThePlatform(): void
    {
        $this->login();

        $problem = $this->requestJson('PATCH', '/api/me/sign-in/login-email', ['email' => 'assistant@solean-it.io']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('email', $problem['violations'][0]['propertyPath']);
    }

    public function testIChangeMyPasswordAndSignInWithIt(): void
    {
        $this->login();

        $this->requestJson('PATCH', '/api/me/sign-in/password', ['password' => 'a long new password']);

        $this->assertResponseStatusCodeSame(204);
        $this->assertTrue($this->signsInWith('a long new password'));
        $this->assertFalse($this->signsInWith('password'));
    }

    public function testAPasswordIsLongEnough(): void
    {
        $this->login();

        $problem = $this->requestJson('PATCH', '/api/me/sign-in/password', ['password' => 'short']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('password', $problem['violations'][0]['propertyPath']);
    }

    private function signsInWith(string $password): bool
    {
        $pool = self::getContainer()->get(UserPool::class);

        try {
            self::getContainer()->get(CognitoIdentityProviderClient::class)->adminInitiateAuth([
                'UserPoolId' => $pool->id(),
                'ClientId' => $pool->clientId(),
                'AuthFlow' => 'ADMIN_USER_PASSWORD_AUTH',
                'AuthParameters' => ['USERNAME' => 'prospector@solean-it.io', 'PASSWORD' => $password],
            ]);

            return true;
        } catch (CognitoIdentityProviderException) {
            return false;
        }
    }
}
