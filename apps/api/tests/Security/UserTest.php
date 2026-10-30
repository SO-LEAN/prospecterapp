<?php

namespace Tests\App\Security;

use App\Security\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Application\MyAccount;

#[Group('unit')]
class UserTest extends TestCase
{
    public function testTheSignedInUserCarriesTheIdentitiesOfTheMemberAndItsAccount(): void
    {
        $userId = '0190a1c0-0000-7000-8000-000000000001';
        $accountId = '0190a1c0-0000-7000-8000-000000000002';
        $personId = '0190a1c0-0000-7000-8000-000000000003';

        $signedIn = User::from(new MyAccount(
            memberId: $userId,
            accountId: $accountId,
            personId: $personId,
            roles: ['ROLE_USER', 'ROLE_PROSPECTOR'],
            organizationCorporateName: 'SOLEAN IT',
            organizationForm: null,
            organizationType: null,
            organizationLogo: null,
            organizationStreet: null,
            organizationPostalCode: null,
            organizationCity: null,
            organizationCountry: null,
            organizationLongitude: null,
            organizationLatitude: null,
            accountIntents: [],
            createdAt: new \DateTimeImmutable(),
            modifiedAt: new \DateTimeImmutable(),
        ));

        $this->assertSame($userId, $signedIn->memberId());
        $this->assertSame($accountId, $signedIn->accountId());
        $this->assertSame($personId, $signedIn->personId());
        $this->assertSame($userId, $signedIn->getUserIdentifier(), 'the subject of the access token');
        $this->assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $signedIn->getRoles());
    }
}
