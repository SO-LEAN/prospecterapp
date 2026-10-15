<?php

namespace Tests\App\Security;

use App\Security\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Application\AccountResponse;
use Prospecter\Core\Accounts\Domain\Account;
use Prospecter\Core\Accounts\Domain\Member;
use Prospecter\Core\Accounts\Domain\Role;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\LegalIdentity;
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

        $signedIn = User::from(AccountResponse::of(Account::open($accountId, new LegalIdentity('SOLEAN IT'), new \DateTimeImmutable()), Member::register($userId, $accountId, $personId, Role::User, Role::Prospector)));

        $this->assertSame($userId->value, $signedIn->memberId());
        $this->assertSame($accountId->value, $signedIn->accountId());
        $this->assertSame($personId->value, $signedIn->personId());
        $this->assertSame($userId->value, $signedIn->getUserIdentifier(), 'the subject of the access token');
        $this->assertSame(['ROLE_USER', 'ROLE_PROSPECTOR'], $signedIn->getRoles());
    }
}
