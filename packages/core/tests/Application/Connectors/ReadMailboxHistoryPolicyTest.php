<?php

declare(strict_types=1);

namespace Tests\Core\Application\Connectors;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Application\Policy\ReadMailboxHistoryPolicy;
use Prospecter\Core\Connectors\Application\ReadMailboxHistory\ReadMailboxHistory;
use Prospecter\Core\Connectors\Domain\Event\MailboxChanged;
use Prospecter\Core\Connectors\Domain\Event\MailWatchRenewed;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class ReadMailboxHistoryPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAMailboxThatChangedIsRead(): void
    {
        $changed = new MailboxChanged(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));

        $this->assertEquals(self::reading(), new ReadMailboxHistoryPolicy()($changed));
    }

    public function testAMailboxWhoseWatchWasRenewedIsRead(): void
    {
        $renewed = new MailWatchRenewed(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), new \DateTimeImmutable(self::AT));

        $this->assertEquals(self::reading(), new ReadMailboxHistoryPolicy()($renewed));
    }

    private static function reading(): ReadMailboxHistory
    {
        return new ReadMailboxHistory(self::ACCOUNT, self::MEMBER, new \DateTimeImmutable(self::AT));
    }
}
