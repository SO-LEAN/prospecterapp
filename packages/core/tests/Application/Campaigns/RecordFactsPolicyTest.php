<?php

declare(strict_types=1);

namespace Tests\Core\Application\Campaigns;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Campaigns\Application\Policy\RecordFactsPolicy;
use Prospecter\Core\Campaigns\Application\Tagging\RecordContactFacts\RecordContactFacts;
use Prospecter\Core\Campaigns\Application\Tagging\RecordOrganizationFacts\RecordOrganizationFacts;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;

class RecordFactsPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string AGGREGATE = '01960000-0000-7000-8000-000000000002';
    private const string CONTACT = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAnOrganizationsFactIsRecordedWithItsContactsAndItsLocality(): void
    {
        $changed = new OrganizationFactsChanged(self::ACCOUNT, self::AGGREGATE, 3, true, true, [self::CONTACT], '75056', 'FR', new \DateTimeImmutable(self::AT));

        $this->assertEquals(new RecordOrganizationFacts(
            accountId: self::ACCOUNT,
            organizationId: self::AGGREGATE,
            revision: 3,
            present: true,
            contacts: [self::CONTACT],
            municipalityCode: '75056',
            country: 'FR',
            executedAt: new \DateTimeImmutable(self::AT),
        ), new RecordFactsPolicy()($changed));
    }

    public function testAnOrganizationsFactThatCarriesNoContactsRecordsNothing(): void
    {
        $firstVersion = new OrganizationFactsChanged(self::ACCOUNT, self::AGGREGATE, 3, true, true, null, null, null, new \DateTimeImmutable(self::AT));

        $this->assertNull(new RecordFactsPolicy()($firstVersion));
    }

    public function testAContactsFactIsRecordedWithHowTheContactIsReached(): void
    {
        $changed = new ContactFactsChanged(self::ACCOUNT, self::CONTACT, 2, true, true, false, true, false, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new RecordContactFacts(
            accountId: self::ACCOUNT,
            contactId: self::CONTACT,
            revision: 2,
            present: true,
            hasEmail: true,
            hasPhone: false,
            hasMobile: true,
            hasLinkedIn: false,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new RecordFactsPolicy()($changed));
    }

    public function testAContactsFactThatCarriesNoReachRecordsNothing(): void
    {
        $firstVersion = new ContactFactsChanged(self::ACCOUNT, self::CONTACT, 2, true, null, null, null, null, new \DateTimeImmutable(self::AT));

        $this->assertNull(new RecordFactsPolicy()($firstVersion));
    }
}
