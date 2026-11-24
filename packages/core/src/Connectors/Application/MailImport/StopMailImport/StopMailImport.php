<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\MailImport\StopMailImport;

/**
 * I stop my e-mail import. My mailbox no longer notifies Prospecter. The e-mails already imported stay, and my
 * calendar stays connected (ADR 0154).
 */
final readonly class StopMailImport
{
    public function __construct(
        public string $memberId,
        public string $provider,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
