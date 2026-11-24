<?php

declare(strict_types=1);

namespace Tests\Core\Application\Prospecting;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Prospecting\Application\DiscardAttachmentsOfErasedExchange\DiscardAttachmentsOfErasedExchange;
use Prospecter\Core\Prospecting\Application\Policy\DiscardAttachmentsOfErasedExchangePolicy;
use Prospecter\Core\Prospecting\Domain\Event\ExchangeErased;
use Prospecter\Core\Prospecting\Domain\Model\ExchangeId;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class DiscardAttachmentsOfErasedExchangePolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string EXCHANGE = '01960000-0000-7000-8000-000000000003';
    private const array FILES = ['attachments/offer.pdf', 'attachments/quote.pdf'];
    private const string AT = '2026-11-24 09:00 UTC';

    public function testAnErasedExchangesFilesLeaveThePrivateStorage(): void
    {
        $erased = new ExchangeErased(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new ExchangeId(self::EXCHANGE), self::FILES, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new DiscardAttachmentsOfErasedExchange(self::FILES, new \DateTimeImmutable(self::AT)), new DiscardAttachmentsOfErasedExchangePolicy()($erased));
    }
}
