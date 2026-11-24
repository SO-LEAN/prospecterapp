<?php

declare(strict_types=1);

namespace Tests\Core\Application\PersonalData;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Application\Contact\ForgetContactLeftUnattached\ForgetContactLeftUnattached;
use Prospecter\Core\PersonalData\Application\Policy\ForgetContactLeftUnattachedPolicy;
use Prospecter\Core\Prospecting\Contract\Event\ContactLeftUnattached;

class ForgetContactLeftUnattachedPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string HOLDER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testAContactNobodyAttachedIsForgottenByTheirHolder(): void
    {
        $left = new ContactLeftUnattached(self::ACCOUNT, self::HOLDER, self::CONTACT, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new ForgetContactLeftUnattached(self::HOLDER, self::CONTACT, new \DateTimeImmutable(self::AT)), new ForgetContactLeftUnattachedPolicy()($left));
    }
}
