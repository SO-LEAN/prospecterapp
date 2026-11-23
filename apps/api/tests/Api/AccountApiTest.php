<?php

declare(strict_types=1);

namespace Tests\App\Api;

use PHPUnit\Framework\Attributes\Group;
use Prospecter\Core\PersonalData\Domain\Model\ContactCard;
use Prospecter\Core\PersonalData\Domain\Model\Person;
use Prospecter\Core\PersonalData\Domain\Model\PersonId;
use Prospecter\Core\PersonalData\Domain\Model\Purpose;
use Prospecter\Core\PersonalData\Domain\Repository\PersonRepository;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\ContactDetails;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\Gesture;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Tests\App\Base\ApiTestCase;
use Tests\App\Base\ReadsResources;

#[Group('api')]
class AccountApiTest extends ApiTestCase
{
    use ReadsResources;

    protected function setUp(): void
    {
        parent::setUp();
        $this->login();
    }

    public function testConsultMyAccount(): void
    {
        $account = $this->requestJson('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $this->assertSame('prospector@solean-it.io', self::valueAt($account, 'contactDetails', 'email'));
        $this->assertSame('SOLEAN IT', self::valueAt($account, 'organization', 'legalIdentity', 'corporateName'));
    }

    public function testTheLinksOfferEveryChangeOfMyAccount(): void
    {
        $links = self::recordAt($this->requestJson('GET', '/api/me'), '_links');

        $this->assertSame(['href' => '/api/me', 'method' => 'GET'], $links['self']);
        $this->assertSame(['href' => '/api/me/name', 'method' => 'PATCH'], $links['changeMyName']);
        $this->assertSame(['href' => '/api/me/picture', 'method' => 'POST'], $links['changeMyPicture']);
        $this->assertSame(['href' => '/api/me/organization/logo', 'method' => 'POST'], $links['changeMyOrganizationLogo']);
        $this->assertSame(['href' => '/api/me/organization/address', 'method' => 'PATCH'], $links['relocateMyOrganization']);
    }

    public function testEachIntentionChangesOnlyItsPart(): void
    {
        $named = $this->requestJson('PATCH', '/api/me/name', ['firstName' => 'Ada', 'lastName' => 'Lovelace']);
        $this->assertResponseIsSuccessful();
        $this->assertSame(['firstName' => 'Ada', 'lastName' => 'Lovelace'], $named['name']);
        $this->assertSame('prospector@solean-it.io', self::valueAt($named, 'contactDetails', 'email'));

        $contacted = $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'ada@solean-it.io', 'phoneNumber' => null, 'language' => 'FR', 'links' => ['linkedin.com/in/ada-lovelace']]);
        $this->assertSame('ada@solean-it.io', self::valueAt($contacted, 'contactDetails', 'email'));
        $this->assertSame([['source' => 'linkedInProfile', 'identifier' => 'ada-lovelace', 'address' => 'https://linkedin.com/in/ada-lovelace']], self::valueAt($contacted, 'contactDetails', 'links'));
        $this->assertSame('Ada', self::valueAt($contacted, 'name', 'firstName'));

        $revised = $this->requestJson('PATCH', '/api/me/organization/legal-identity', ['corporateName' => 'SO-LEAN', 'form' => 'SARL', 'type' => null]);
        $this->assertSame('SO-LEAN', self::valueAt($revised, 'organization', 'legalIdentity', 'corporateName'));

        $relocated = $this->requestJson('PATCH', '/api/me/organization/address', ['street' => '1 place Kléber', 'postalCode' => '67000', 'city' => 'Strasbourg', 'country' => 'FR']);
        $this->assertSame(['street' => '1 place Kléber', 'postalCode' => '67000', 'city' => 'Strasbourg', 'country' => 'FR'], self::valueAt($relocated, 'organization', 'address'));
        $this->assertNull(self::valueAt($relocated, 'organization', 'location'), 'the geocoder of the tests answers nothing');
        $this->assertSame('SO-LEAN', self::valueAt($relocated, 'organization', 'legalIdentity', 'corporateName'));

        $erased = $this->requestJson('PATCH', '/api/me/organization/address', ['street' => null, 'postalCode' => null, 'city' => null, 'country' => null]);
        $this->assertNull(self::valueAt($erased, 'organization', 'address'));
    }

    public function testMyEmailCannotBeTheOneOfAColleagueButMayBeTheOneOfAnotherAccount(): void
    {
        $this->aPersonWithTheEmail('grace@solean-it.io', $this->accountOfTheFixtures());
        $this->aPersonWithTheEmail('linus@competitor.io', new AccountId(self::newIdentity()));

        $problem = $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'grace@solean-it.io', 'phoneNumber' => null, 'language' => null]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(['email'], $this->violatedFields($problem));

        $this->requestJson('PATCH', '/api/me/contact-details', ['email' => 'linus@competitor.io', 'phoneNumber' => null, 'language' => null]);
        $this->assertResponseIsSuccessful();
    }

    public function testChangeMyPictureAndMyOrganizationLogo(): void
    {
        $this->assertStringEndsWith('.png', self::stringAt($this->uploadImage('/api/me/picture'), 'picture', 'url'));
        $this->assertStringEndsWith('.png', self::stringAt($this->uploadImage('/api/me/organization/logo'), 'organization', 'logo', 'url'));
    }

    private function aPersonWithTheEmail(string $email, AccountId $account): void
    {
        self::service(PersonRepository::class)->save(Person::collect(new Gesture($account, new MemberId(self::newIdentity()), new \DateTimeImmutable(self::THE_MOMENT_OF_THE_TESTS)), new PersonId(self::newIdentity()), Purpose::Membership, new ContactCard(contactDetails: new ContactDetails(new Email($email)))));
    }
}
