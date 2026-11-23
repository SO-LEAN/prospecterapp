<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application;

/**
 * A campaign's recipient as the team reads it (ADR 0145). It holds the recipient's status (`pending`, `processed`,
 * `dismissed` or `erased`), and whether they are still to handle, as the domain says. A processed recipient holds
 * whether the call went through, the id of the exchange that processed them (ADR 0152), and when it happened. A
 * dismissed recipient holds the reason, when one was given. It also holds whether the contact was erased from a started
 * campaign. What the team did with them is then kept.
 */
final readonly class RecipientInSheet
{
    public function __construct(
        public string $status,
        public bool $pending,
        public bool $missed,
        public ?string $reason,
        public ?string $exchangeId = null,
        public bool $erased = false,
        public ?\DateTimeImmutable $processedAt = null,
    ) {
    }
}
