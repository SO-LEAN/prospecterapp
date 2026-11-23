<?php

declare(strict_types=1);

namespace Tests\Core\Application\Reporting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\PersonalData\Contract\Event\ContactFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\Prospecting\Contract\Event\OrganizationFactsChanged;
use Prospecter\Core\Reporting\Application\Policy\RecordFactsPolicy;
use Prospecter\Core\Reporting\Application\RecordContactFacts\RecordContactFacts;
use Prospecter\Core\Reporting\Application\RecordExchangeFacts\RecordExchangeFacts;
use Prospecter\Core\Reporting\Application\RecordOrganizationFacts\RecordOrganizationFacts;

class RecordFactsPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string AGGREGATE = '01960000-0000-7000-8000-000000000002';
    private const string AT = '2026-11-23 09:00 UTC';
    private const string NOTED = '2026-11-20 14:00 UTC';

    public function testAnOrganizationsFactIsRecorded(): void
    {
        $changed = new OrganizationFactsChanged(self::ACCOUNT, self::AGGREGATE, 3, true, false, [], '75056', 'FR', new \DateTimeImmutable(self::AT));

        $this->assertEquals(new RecordOrganizationFacts(self::ACCOUNT, self::AGGREGATE, 3, true, false, new \DateTimeImmutable(self::AT)), new RecordFactsPolicy()($changed));
    }

    public function testAnExchangesFactIsRecorded(): void
    {
        $changed = new ExchangeFactsChanged(self::ACCOUNT, self::AGGREGATE, 2, true, 'done', new \DateTimeImmutable(self::NOTED), new \DateTimeImmutable(self::AT), contactId: self::ACCOUNT);

        $this->assertEquals(new RecordExchangeFacts(self::ACCOUNT, self::AGGREGATE, 2, true, 'done', new \DateTimeImmutable(self::NOTED), new \DateTimeImmutable(self::AT)), new RecordFactsPolicy()($changed));
    }

    public function testAContactsFactIsRecorded(): void
    {
        $changed = new ContactFactsChanged(self::ACCOUNT, self::AGGREGATE, 0, false, null, null, null, null, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new RecordContactFacts(self::ACCOUNT, self::AGGREGATE, 0, false, new \DateTimeImmutable(self::AT)), new RecordFactsPolicy()($changed));
    }
}
