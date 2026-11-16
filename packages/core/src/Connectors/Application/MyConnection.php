<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application;

/**
 * A connection of mine as I see it: the provider (`google`), its state (`connected`, `toReconnect`), the account
 * connected there and since when, and how many of my exchanges to come are synced there, and failed (ADR 0133).
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
    ) {
    }
}
