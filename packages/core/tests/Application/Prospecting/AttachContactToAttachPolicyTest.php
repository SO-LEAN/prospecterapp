<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\Contact\AttachContactToAttach\AttachContactToAttach;
use Prospecter\Core\Prospecting\Application\Policy\AttachContactToAttachPolicy;
use Prospecter\Core\Prospecting\Domain\Event\ContactAdded;
use Prospecter\Core\Prospecting\Domain\Model\ContactId;
use Prospecter\Core\Prospecting\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class AttachContactToAttachPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string ORGANIZATION = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testAContactToAttachAddedToAnOrganizationIsAttached(): void
    {
        $event = new ContactAdded(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new OrganizationId(self::ORGANIZATION), new ContactId(self::CONTACT), new \DateTimeImmutable(self::AT));

        $this->assertEquals(new AttachContactToAttach(
            memberId: self::MEMBER,
            organizationId: self::ORGANIZATION,
            contactId: self::CONTACT,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new AttachContactToAttachPolicy()($event));
    }
}
