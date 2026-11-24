<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\Contact\PushBackErasureOfContactToAttach\PushBackErasureOfContactToAttach;
use Prospecter\Core\Prospecting\Application\Policy\PushBackErasureOfContactToAttachPolicy;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeCorrected;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeMoved;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeNoted;
use Prospecter\Core\Prospecting\Domain\Event\ExchangePlanned;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class PushBackErasureOfContactToAttachPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000003';
    private const string AT = '2026-11-25 09:00 UTC';

    public function testAPlannedExchangeMayPushBackTheErasureOfItsContactToAttach(): void
    {
        $this->assertEquals(self::pushingBack(), new PushBackErasureOfContactToAttachPolicy()(new ExchangePlanned(...self::exchange())));
    }

    public function testANotedExchangeMayPushBackTheErasureOfItsContactToAttach(): void
    {
        $this->assertEquals(self::pushingBack(), new PushBackErasureOfContactToAttachPolicy()(new ExchangeNoted(...self::exchange())));
    }

    public function testAMovedExchangeMayPushBackTheErasureOfItsContactToAttach(): void
    {
        $this->assertEquals(self::pushingBack(), new PushBackErasureOfContactToAttachPolicy()(new ExchangeMoved(...self::exchange())));
    }

    public function testACorrectedExchangeMayPushBackTheErasureOfItsContactToAttach(): void
    {
        $this->assertEquals(self::pushingBack(), new PushBackErasureOfContactToAttachPolicy()(new ExchangeCorrected(...self::exchange())));
    }

    /**
     * @return array{AccountId, MemberId, ExchangeId, \DateTimeImmutable}
     */
    private static function exchange(): array
    {
        return [new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ExchangeId(self::EXCHANGE), new \DateTimeImmutable(self::AT)];
    }

    private static function pushingBack(): PushBackErasureOfContactToAttach
    {
        return new PushBackErasureOfContactToAttach(self::EXCHANGE, new \DateTimeImmutable(self::AT));
    }
}
