<?php

declare(strict_types=1);

namespace Tests\Core\Application\Accounts;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Accounts\Application\Policy\RegisterTheFounderPolicy;
use Prospecter\Core\Accounts\Application\RegisterTheFounder\RegisterTheFounder;
use Prospecter\Core\Accounts\Domain\Event\AccountOpened;
use Prospecter\Core\Accounts\Domain\Model\ProfileId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class RegisterTheFounderPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string FOUNDER = '01960000-0000-7000-8000-000000000002';
    private const string PROFILE = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testTheFounderOfAnOpenedAccountIsRegisteredWithTheirProfile(): void
    {
        $opened = new AccountOpened(new AccountId(self::ACCOUNT), new MemberId(self::FOUNDER), new ProfileId(self::PROFILE), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new RegisterTheFounder(self::ACCOUNT, self::FOUNDER, self::PROFILE, new \DateTimeImmutable(self::AT)), new RegisterTheFounderPolicy()($opened));
    }
}
