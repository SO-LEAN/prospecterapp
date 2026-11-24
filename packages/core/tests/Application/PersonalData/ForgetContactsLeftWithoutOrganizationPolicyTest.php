<?php

declare(strict_types=1);

namespace Tests\Core\Application\PersonalData;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Application\Contact\ForgetContactsLeftWithoutOrganization\ForgetContactsLeftWithoutOrganization;
use Prospecter\Core\PersonalData\Application\Policy\ForgetContactsLeftWithoutOrganizationPolicy;
use Prospecter\Core\Prospecting\Contract\Event\ContactsLeftWithoutOrganization;

class ForgetContactsLeftWithoutOrganizationPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string ORGANIZATION = '01960000-0000-7000-8000-000000000003';
    private const string CONTACT = '01960000-0000-7000-8000-000000000004';
    private const string AT = '2026-11-24 09:00 UTC';

    public function testTheContactsNoOrganizationNamesAreForgottenByTheMemberWhoLeftThem(): void
    {
        $left = new ContactsLeftWithoutOrganization(self::ACCOUNT, self::MEMBER, self::ORGANIZATION, [self::CONTACT], new \DateTimeImmutable(self::AT));

        $this->assertEquals(new ForgetContactsLeftWithoutOrganization(self::MEMBER, [self::CONTACT], new \DateTimeImmutable(self::AT)), new ForgetContactsLeftWithoutOrganizationPolicy()($left));
    }
}
