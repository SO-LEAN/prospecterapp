<?php

declare(strict_types=1);

namespace Tests\Core\Application\Connectors;

use PHPUnit\Framework\TestCase;
use Prospecter\Core\Connectors\Application\MailImport\ImportMail\ImportMail;
use Prospecter\Core\Connectors\Application\Policy\ImportMailPolicy;
use Prospecter\Core\Connectors\Domain\Event\MailboxMessageAdded;
use Prospecter\Core\SharedKernel\Domain\Identity\AccountId;
use Prospecter\Core\SharedKernel\Domain\Identity\MemberId;

class ImportMailPolicyTest extends TestCase
{
    private const string ACCOUNT = '01960000-0000-7000-8000-000000000001';
    private const string MEMBER = '01960000-0000-7000-8000-000000000002';
    private const string MESSAGE = '18c2f0a1b3d4e5f6';
    private const string AT = '2026-11-23 09:00 UTC';

    public function testAMessageAddedToAMailboxIsImported(): void
    {
        $added = new MailboxMessageAdded(new AccountId(self::ACCOUNT), new MemberId(self::MEMBER), self::MESSAGE, new \DateTimeImmutable(self::AT));

        $this->assertEquals(new ImportMail(
            accountId: self::ACCOUNT,
            memberId: self::MEMBER,
            messageId: self::MESSAGE,
            executedAt: new \DateTimeImmutable(self::AT),
        ), new ImportMailPolicy()($added));
    }
}
