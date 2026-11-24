<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\DismissRecipient;

/** Dismisses a started campaign's recipient without an exchange, for a reason when the member gives one. */
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
