<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;
use Prospecter\Core\Prospecting\Application\DetachForgottenContact\DetachForgottenContact;
use Prospecter\Core\Prospecting\Application\Policy\DetachForgottenContactPolicy;

class DetachForgottenContactPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testAForgottenContactLeavesItsOrganizations(): void
    {
        $event = new ContactForgotten(self::ACCOUNT, self::MEMBER, self::CONTACT, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new DetachForgottenContact(
            accountId: self::ACCOUNT,
            memberId: self::MEMBER,
            contactId: self::CONTACT,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new DetachForgottenContactPolicy()($event));
    }
}
