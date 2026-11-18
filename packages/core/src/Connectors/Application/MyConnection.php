<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * A connection of mine as I see it: the provider (`google`), its state (`connected`, `toReconnect`), the account
 * connected there and since when, how many of my exchanges to come are synced there, and failed (ADR 0133), and
 * where the import of my e-mails stands (`off`, `active`, `toReconnect`) and when my mailbox last notified it
 * (ADR 0154).
 */
final readonly class MyConnection
{
    public function __construct(
        public string $provider,
        public string $state,
        public string $account,
        public \DateTimeImmutable $connectedAt,
        public int $synced,
        public int $failed,
        public string $mailImport,
        public ?\DateTimeImmutable $lastNotifiedAt = null,
    ) {
    }
}
