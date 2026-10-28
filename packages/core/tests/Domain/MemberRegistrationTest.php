<?php

declare(strict_types=1);

namespace Tests\Core\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Domain\Event\MemberRegistered;
use Prospecter\Core\Accounts\Domain\Exception\AdministratorsOnly;
use Prospecter\Core\Accounts\Domain\Model\Member;
use Prospecter\Core\Accounts\Domain\Model\Role;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;

/**
 * Only an administrator registers a member, and the registration records what happened.
 */
class MemberRegistrationTest extends TestCase
{
    private const string ACCOUNT = '0190a1c0-0000-7000-8000-000000000001';
    private const string NEW_MEMBER = '0190a1c0-0000-7000-8000-0000000000cc';
    private const string NEW_PROFILE = '0190a1c0-0000-7000-8000-0000000000dd';

    public function testARegistrationByAnAdministratorRecordsIt(): void
    {
        $member = $this->registerBy($this->memberWith(...Role::cases()));

        $this->assertEquals(
            [new MemberRegistered(new AccountId(self::ACCOUNT), new MemberId('0190a1c0-0000-7000-8000-0000000000aa'), new MemberId(self::NEW_MEMBER), new PersonId(self::NEW_PROFILE), new \DateTimeImmutable('2026-10-20 14:00'))],
            $member->releaseEvents(),
        );
    }

    /**
     * @param list<Role> $roles
     */
    #[DataProvider('membersWhoAreNoAdministrator')]
    public function testOnlyAnAdministratorRegistersAMember(array $roles): void
    {
        $this->expectException(AdministratorsOnly::class);

        $this->registerBy($this->memberWith(...$roles));
    }

    /**
     * @return iterable<string, array{list<Role>}>
     */
    public static function membersWhoAreNoAdministrator(): iterable
    {
        yield 'a prospector' => [[Role::User, Role::Prospector]];
        yield 'a manager of personal data' => [[Role::User, Role::Prospector, Role::PersonalData]];
    }

    private function memberWith(Role ...$roles): Member
    {
        return Member::register(new MemberId('0190a1c0-0000-7000-8000-0000000000aa'), new AccountId(self::ACCOUNT), new PersonId('0190a1c0-0000-7000-8000-0000000000bb'), ...$roles);
    }

    private function registerBy(Member $administrator): Member
    {
        return Member::registerBy($administrator, new MemberId(self::NEW_MEMBER), new PersonId(self::NEW_PROFILE), new \DateTimeImmutable('2026-10-20 14:00'), Role::Prospector);
    }
}
