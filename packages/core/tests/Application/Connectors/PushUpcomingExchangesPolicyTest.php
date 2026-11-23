<?php

declare(strict_types=1);

namespace Tests\Core\Application\Connectors;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Application\Policy\PushUpcomingExchangesPolicy;
use Prospecter\Core\Connectors\Application\PushUpcomingExchanges\PushUpcomingExchanges;
use Prospecter\Core\Connectors\Domain\Event\CalendarRecreated;
use Prospecter\Core\Connectors\Domain\Event\Connected;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class PushUpcomingExchangesPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAMemberWhoConnectsGetsTheirUpcomingExchangesPushed(): void
    {
        $connected = new Connected(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));

        $this->assertEquals(self::pushing(), new PushUpcomingExchangesPolicy()($connected));
    }

    public function testACalendarCreatedAgainGetsTheUpcomingExchangesPushed(): void
    {
        $recreated = new CalendarRecreated(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));

        $this->assertEquals(self::pushing(), new PushUpcomingExchangesPolicy()($recreated));
    }

    private static function pushing(): PushUpcomingExchanges
    {
        return new PushUpcomingExchanges(self::ACCOUNT, self::MEMBER, new \DateTimeImmutable(self::AT));
    }
}
