<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Contract\Event\VideoCallLinkCreated;
use Prospecter\Core\Prospecting\Application\KeepCreatedVideoCallLink\KeepCreatedVideoCallLink;
use Prospecter\Core\Prospecting\Application\Policy\KeepCreatedVideoCallLinkPolicy;

class KeepCreatedVideoCallLinkPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000003';
    private const string LINK = 'https://meet.google.com/abc-defg-hij';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testAVideoCallKeepsTheLinkItsCalendarCreated(): void
    {
        $created = new VideoCallLinkCreated(self::ACCOUNT, self::MEMBER, self::EXCHANGE, self::LINK, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new KeepCreatedVideoCallLink(self::EXCHANGE, self::LINK, new \DateTimeImmutable(self::AT)), new KeepCreatedVideoCallLinkPolicy()($created));
    }
}
