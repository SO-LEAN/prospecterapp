<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;

/**
 * An exchange's push state in my calendar at a provider (ADR 0133). It is pending until a push reaches the calendar.
 * It is synced once a push did. It is failed once the provider refused the push. It also says since when, and why.
 */
final readonly class SyncResource
{
    public function __construct(
        #[OA\Property(enum: ['google'])]
        public string $provider,
        #[OA\Property(enum: ['pending', 'synced', 'failed'])]
        public string $state,
        #[OA\Property(description: 'Since when the push has this state. None for an exchange never pushed')]
        public ?\DateTimeImmutable $at = null,
        #[OA\Property(description: 'Why: the provider refused the push (`calendarRefusedThePush`), or my connection is to reconnect (`toReconnect`)', enum: ['calendarRefusedThePush', 'toReconnect'])]
        public ?string $reason = null,
    ) {
    }
}
