<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\StopMailImport;

/**
 * I stop the import of my e-mails: my mailbox no longer notifies Prospecter, the e-mails already imported stay, my
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
