<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Connection;

/**
 * One of my connections, as I see it. It holds the provider (`google`), its state (`connected`, `toReconnect`), and
 * the account connected there and since when. It counts my upcoming exchanges that are synced there, and those that
 * failed (ADR 0133). It also says where my e-mail import stands (`off`, `active`, `toReconnect`), and when my mailbox
 * last notified it (ADR 0154).
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
