<?php

namespace Tests\App\Api;

use App\Auth\Passwords;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Member;
use Tests\App\Base\ApiTestCase;

#[Group('api')]
class AccountApiTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testConsultMyAccount(): void
    {
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertSame('prospector', $account['userName']);
        $this->assertSame('prospector@solean-it.io', $account['contactDetails']['email']);
        $this->assertSame('SOLEAN IT', $account['organization']['legalIdentity']['corporateName']);
    }

    public function testTheLinksOfferEveryChangeOfMyAccount(): void
    {
        $links = $this->requestJson('GET', '/api/me')['_links'];

        $this->assertSame(['href' => '/api/me', 'method' => 'GET'], $links['self']);
        $this->assertSame(['href' => '/api/me/name', 'method' => 'PATCH'], $links['changeMyName']);
        $this->assertSame(['href' => '/api/me/picture', 'method' => 'POST'], $links['changeMyPicture']);
        $this->assertSame(['href' => '/api/me/password', 'method' => 'PATCH'], $links['changeMyPassword']);
        $this->assertSame(['href' => '/api/me/organization/logo', 'method' => 'POST'], $links['changeMyOrganizationLogo']);
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $named = $this->requestJson('PATCH', '/api/me/name', ['firstName' => 'Ada', 'lastName' => 'Lovelace']);
        $this->assertResponseIsSuccessful();
        $this->assertSame(['firstName' => 'Ada', 'lastName' => 'Lovelace'], $named['name']);
        $this->assertSame('prospector@solean-it.io', $named['contactDetails']['email']);

        $contacted = $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'ada@solean-it.io', 'phoneNumber' => null, 'language' => 'FR']);
        $this->assertSame('ada@solean-it.io', $contacted['contactDetails']['email']);
        $this->assertSame('Ada', $contacted['name']['firstName']);

        $revised = $this->requestJson('PATCH', '/api/me/organization/legal-identity', ['corporateName' => 'SO-LEAN', 'form' => 'SARL', 'type' => null]);
        $this->assertSame('SO-LEAN', $revised['organization']['legalIdentity']['corporateName']);
    }

    public function testChangeMyUserName(): void
    {
        $this->assertSame('ada', $this->requestJson('PATCH', '/api/me/user-name', ['userName' => 'ada'])['userName']);
    }

    public function testAUserNameIsRequired(): void
    {
        $problem = $this->requestJson('PATCH', '/api/me/user-name', ['userName' => '']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['userName'], $this->violatedFields($problem));
    }

    public function testChangeMyPictureAndMyOrganizationLogo(): void
    {
        $this->assertStringEndsWith('.png', $this->uploadImage('/api/me/picture')['picture']['url']);
        $this->assertStringEndsWith('.png', $this->uploadImage('/api/me/organization/logo')['organization']['logo']['url']);
    }

    public function testChangeMyPassword(): void
    {
        $this->requestJson('PATCH', '/api/me/password', ['password' => 'a new password']);

        $this->assertResponseStatusCodeSame(204);
        $passwords = self::getContainer()->get(Passwords::class);
        $me = $this->fixtures->getReference('prospector-user', Member::class);
        $this->assertTrue($passwords->verify($me->id(), 'a new password'));
        $this->assertFalse($passwords->verify($me->id(), 'password'), 'the old password no longer signs in');
    }

    public function testAPasswordIsLongEnough(): void
    {
        $this->requestJson('PATCH', '/api/me/password', ['password' => 'short']);

        $this->assertResponseStatusCodeSame(422);
    }
}
