<?php

declare(strict_types=1);

namespace Tests\Core\Application\PersonalData;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Contract\Event\AccountOpened;
use Prospecter\Core\PersonalData\Application\CollectTheFounderProfile\CollectTheFounderProfile;
use Prospecter\Core\PersonalData\Application\Policy\CollectTheFounderProfilePolicy;

class CollectTheFounderProfilePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string FOUNDER = '01960000-0000-7000-8000-000000000002';
    private const string PROFILE = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testAnOpenedAccountsFounderGetsAnEmptyProfile(): void
    {
        $opened = new AccountOpened(self::ACCOUNT, self::FOUNDER, self::PROFILE, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new CollectTheFounderProfile(
            accountId: self::ACCOUNT,
            founderId: self::FOUNDER,
            profileId: self::PROFILE,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new CollectTheFounderProfilePolicy()($opened));
    }
}
