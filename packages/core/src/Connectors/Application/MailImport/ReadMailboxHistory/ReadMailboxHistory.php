<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\ReadMailboxHistory;

final readonly class ReadMailboxHistory
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
