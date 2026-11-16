<?php

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;

/**
 * Where the push of an exchange into my calendar at a provider stands (ADR 0133): pending until a push reaches it,
 * synced once one did, failed once the provider refused it; since when, and why.
 */
final readonly class SyncResource
{
    public function __construct(
        #[OA\Property(enum: ['google'])]
        public string $provider,
        #[OA\Property(enum: ['pending', 'synced', 'failed'])]
        public string $state,
        #[OA\Property(description: 'Since when it stands so; none for an exchange never pushed', nullable: false)]
        public ?\DateTimeImmutable $at = null,
        #[OA\Property(description: 'Why: the provider refused it (`calendarRefusedThePush`), or my connection is to reconnect (`toReconnect`)', enum: ['calendarRefusedThePush', 'toReconnect'], nullable: false)]
        public ?string $reason = null,
    ) {
    }
}
