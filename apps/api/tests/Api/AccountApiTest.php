<?php

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
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
