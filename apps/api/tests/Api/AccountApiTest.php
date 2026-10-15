<?php

namespace Tests\App\Api;

use App\Auth\Passwords;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Entity\User;
use Tests\App\Base\ApiTestCase;

#[Group('api')]
class AccountApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testShowMyAccount(): void
    {
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertSame('prospector', $account['userName']);
        $this->assertSame('SOLEAN IT', $account['organization']['corporateName']);
    }

    public function testReplaceMyAccount(): void
    {
        $account = $this->requestJson('PUT', '/api/me', [
            'userName' => 'prospector',
            'firstName' => 'Ada',
            'lastName' => 'Lovelace',
            'email' => 'ada@solean-it.io',
            'organization' => ['corporateName' => 'SOLEAN'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Ada', $account['firstName']);
        $this->assertSame('SOLEAN', $account['organization']['corporateName']);
    }

    public function testChangeMyPassword(): void
    {
        $this->requestJson('PUT', '/api/me', ['userName' => 'prospector', 'password' => 'a new password']);

        $this->assertResponseIsSuccessful();
        $passwords = self::getContainer()->get(Passwords::class);
        $userId = $this->fixtures->getReference('prospector-user', User::class)->getId();
        $this->assertTrue($passwords->verify($userId, 'a new password'));
        $this->assertFalse($passwords->verify($userId, 'password'), 'the old password no longer signs in');
    }

    public function testLeavingThePasswordOutKeepsIt(): void
    {
        $this->requestJson('PUT', '/api/me', ['userName' => 'prospector', 'firstName' => 'Ada']);

        $this->assertResponseIsSuccessful();
        $userId = $this->fixtures->getReference('prospector-user', User::class)->getId();
        $this->assertTrue(self::getContainer()->get(Passwords::class)->verify($userId, 'password'));
    }

    public function testUploadMyPictureKeepsMyInformation(): void
    {
        $account = $this->uploadImage('/api/me/picture');

        $this->assertResponseIsSuccessful();
        $this->assertStringEndsWith('.png', $account['picture']['url']);
        $this->assertSame('prospector@solean-it.io', $account['email']);
    }

    public function testUserNameIsRequired(): void
    {
        $problem = $this->requestJson('PUT', '/api/me', ['userName' => '']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['userName'], $this->violatedFields($problem));
    }
}
