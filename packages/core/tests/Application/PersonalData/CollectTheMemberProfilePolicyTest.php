<?php

declare(strict_types=1);

namespace Tests\Core\Application\PersonalData;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Contract\Event\MemberRegistered;
use Prospecter\Core\PersonalData\Application\Policy\CollectTheMemberProfilePolicy;
use Prospecter\Core\PersonalData\Application\Profile\CollectTheMemberProfile\CollectTheMemberProfile;

class CollectTheMemberProfilePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string ADMINISTRATOR = '01960000-0000-7000-8000-000000000002';
    private const string MEMBER = '01960000-0000-7000-8000-000000000003';
    private const string PROFILE = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testARegisteredMemberGetsAnEmptyProfile(): void
    {
        $registered = new MemberRegistered(self::ACCOUNT, self::ADMINISTRATOR, self::MEMBER, self::PROFILE, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new CollectTheMemberProfile(
            accountId: self::ACCOUNT,
            administratorId: self::ADMINISTRATOR,
            profileId: self::PROFILE,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new CollectTheMemberProfilePolicy()($registered));
    }
}
