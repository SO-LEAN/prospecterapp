<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\DismissRecipient;

/**
 * Dismisses a recipient of a started campaign without an exchange, for a reason when said.
 */
final readonly class DismissRecipient
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public string $contactId,
        public ?string $reason,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
