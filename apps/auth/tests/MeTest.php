<?php

namespace App\Tests;

use App\DataFixtures\CredentialsFixtures;
use App\LoginEmails;
use App\Passwords;

/**
 * What a member signs in with, changed with an access token of their own.
 */
class MeTest extends AuthTestCase
{
    public function testILinkToTheChangesOfWhatISignInWith(): void
    {
        $this->signInWithAToken();

        $me = $this->requestJson('GET', '/auth/me');

        $this->assertSame(['href' => '/auth/me/login-email', 'method' => 'PATCH'], $me['_links']['changeMyLoginEmail']);
        $this->assertSame(['href' => '/auth/me/password', 'method' => 'PATCH'], $me['_links']['changeMyPassword']);
    }

    public function testIChangeMyLoginEmailAndSignInWithIt(): void
    {
        $this->signInWithAToken();

        $this->assertSame('ada@solean-it.io', $this->requestJson('PATCH', '/auth/me/login-email', ['email' => 'ada@solean-it.io'])['loginEmail']);
        $this->assertSame('ada@solean-it.io', $this->requestJson('GET', '/auth/me')['loginEmail']);
    }

    public function testALoginEmailIsAValidEmail(): void
    {
        $this->signInWithAToken();

        $problem = $this->requestJson('PATCH', '/auth/me/login-email', ['email' => 'not an email']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('email', $problem['violations'][0]['propertyPath']);
    }

    public function testALoginEmailIsUniqueOnThePlatform(): void
    {
        self::getContainer()->get(LoginEmails::class)->assign('01900000-0000-7000-8000-0000000000aa', 'grace@competitor.io');
        $this->signInWithAToken();

        $problem = $this->requestJson('PATCH', '/auth/me/login-email', ['email' => 'grace@competitor.io']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('email', $problem['violations'][0]['propertyPath']);
    }

    public function testIChangeMyPassword(): void
    {
        $this->signInWithAToken();

        $this->requestJson('PATCH', '/auth/me/password', ['password' => 'a long new password']);

        $this->assertResponseStatusCodeSame(204);
        $this->assertTrue(self::getContainer()->get(Passwords::class)->verify(CredentialsFixtures::PROSPECTOR, 'a long new password'));
    }

    public function testAPasswordIsLongEnough(): void
    {
        $this->signInWithAToken();

        $problem = $this->requestJson('PATCH', '/auth/me/password', ['password' => 'short']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('password', $problem['violations'][0]['propertyPath']);
    }
}
