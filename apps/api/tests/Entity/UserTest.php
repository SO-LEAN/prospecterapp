<?php

namespace Tests\App\Entity;

use App\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Member as CoreUser;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

#[Group('unit')]
class UserTest extends TestCase
{
    public function testTheSignedInUserCarriesTheIdentitiesOfTheMemberAndItsAccount(): void
    {
        $userId = new MemberId('0190a1c0-0000-7000-8000-000000000001');
        $accountId = new AccountId('0190a1c0-0000-7000-8000-000000000002');
        $personId = new PersonId('0190a1c0-0000-7000-8000-000000000003');

        $signedIn = User::from(CoreUser::register($userId, $accountId, $personId, Role::User, Role::Prospector), 'ada@solean-it.io');

        $this->assertEquals($userId, $signedIn->memberId());
        $this->assertEquals($accountId, $signedIn->accountId());
        $this->assertEquals($personId, $signedIn->personId());
        $this->assertSame('ada@solean-it.io', $signedIn->getUserIdentifier());
        $this->assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $signedIn->getRoles());
    }
}
