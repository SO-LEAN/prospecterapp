<?php

declare(strict_types=1);

namespace Tests\Core\Application\Campaigns;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Application\Policy\ForgetTagsOfForgottenContactPolicy;
use Prospecter\Core\Campaigns\Application\Tagging\ForgetTagsOfForgottenContact\ForgetTagsOfForgottenContact;
use Prospecter\Core\PersonalData\Contract\Event\ContactForgotten;

class ForgetTagsOfForgottenContactPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAForgottenContactsTagsAreForgotten(): void
    {
        $forgotten = new ContactForgotten(self::ACCOUNT, self::MEMBER, self::CONTACT, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new ForgetTagsOfForgottenContact(self::ACCOUNT, self::CONTACT, new \DateTimeImmutable(self::AT)), new ForgetTagsOfForgottenContactPolicy()($forgotten));
    }
}
