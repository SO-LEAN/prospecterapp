<?php

declare(strict_types=1);

namespace Tests\Core\Application\Connectors;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Application\Policy\PushExchangePolicy;
use Prospecter\Core\Connectors\Application\PushExchange\PushExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;

class PushExchangePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000002';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAChangedExchangeIsPushedAsItIsNow(): void
    {
        $changed = new ExchangeFactsChanged(self::ACCOUNT, self::EXCHANGE, 2, true, 'planned', null, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new PushExchange(self::ACCOUNT, self::EXCHANGE, new \DateTimeImmutable(self::AT)), new PushExchangePolicy()($changed));
    }
}
