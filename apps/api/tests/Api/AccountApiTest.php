<?php

namespace Tests\App\Api;

use App\Auth\LoginEmails;
use App\Auth\Passwords;
use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\AccountRepository;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\MemberRepository;
use Prospecter\Core\PersonalData\Domain\Person;
use Prospecter\Core\PersonalData\Domain\PersonRepository;
use Prospecter\Core\PersonalData\Domain\Purpose;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
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
        $this->assertSame('prospector@solean-it.io', $account['loginEmail']);
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

    public function testMyEmailCannotBeTheOneOfAColleagueButMayBeTheOneOfAnotherAccount(): void
    {
        $this->aPersonWithTheEmail('grace@solean-it.io', $this->fixtures->getReference('solean-it-account', Account::class)->id());
        $this->aPersonWithTheEmail('linus@competitor.io', self::getContainer()->get(AccountRepository::class)->nextIdentity());

        $problem = $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'grace@solean-it.io', 'phoneNumber' => null, 'language' => null]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem));

        $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'linus@competitor.io', 'phoneNumber' => null, 'language' => null]);
        $this->assertResponseIsSuccessful();
    }

    public function testChangeMyLoginEmail(): void
    {
        $account = $this->requestJson('PATCH', '/api/me/login-email', ['email' => 'ada@solean-it.io']);

        $this->assertSame('ada@solean-it.io', $account['loginEmail']);
        $this->assertSame('prospector@solean-it.io', $account['contactDetails']['email'], 'the contact email of my profile is another thing');
    }

    public function testALoginEmailIsAValidEmail(): void
    {
        $problem = $this->requestJson('PATCH', '/api/me/login-email', ['email' => 'not an email']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem));
    }

    public function testALoginEmailIsUniqueOnThePlatform(): void
    {
        self::getContainer()->get(LoginEmails::class)->assign(self::getContainer()->get(MemberRepository::class)->nextIdentity(), new Email('grace@competitor.io'));

        $problem = $this->requestJson('PATCH', '/api/me/login-email', ['email' => 'grace@competitor.io']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem));
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

    private function aPersonWithTheEmail(string $email, AccountId $account): void
    {
        $persons = self::getContainer()->get(PersonRepository::class);
        $persons->save(Person::collect($persons->nextIdentity(), $account, Purpose::Membership, contactDetails: new ContactDetails(new Email($email))));
    }
}
