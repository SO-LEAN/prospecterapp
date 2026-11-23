<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\ImportMail;

final readonly class ImportMail
{
    /**
     * @param string $messageId the message's identity in the member's mailbox at the provider
     */
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $messageId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
