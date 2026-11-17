<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * A recipient of a campaign as the team reads it (ADR 0145): its status (`pending`, `processed`, `dismissed` or
 * `erased`), whether it is still to handle, as the domain says; processed, whether the call did not go through;
 * dismissed, why, when said.
 */
final readonly class RecipientInSheet
{
    public function __construct(
        public string $status,
        public bool $pending,
        public bool $missed,
        public ?string $reason,
    ) {
    }
}
