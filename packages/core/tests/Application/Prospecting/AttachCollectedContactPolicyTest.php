<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Contract\Event\ContactCollected;
use Prospecter\Core\Prospecting\Application\AttachCollectedContact\AttachCollectedContact;
use Prospecter\Core\Prospecting\Application\Policy\AttachCollectedContactPolicy;

class AttachCollectedContactPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string ORGANIZATION = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testACollectedContactIsAttachedToItsOrganizations(): void
    {
        $event = new ContactCollected(self::ACCOUNT, self::MEMBER, self::CONTACT, [self::ORGANIZATION], new \DateTimeImmutable(self::AT));

        $this->assertEquals(new AttachCollectedContact(
            memberId: self::MEMBER,
            contactId: self::CONTACT,
            organizationIds: [self::ORGANIZATION],
            executedAt: new \DateTimeImmutable(self::AT),
        ), new AttachCollectedContactPolicy()($event));
    }
}
