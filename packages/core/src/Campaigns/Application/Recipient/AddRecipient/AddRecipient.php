<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\AddRecipient;

final readonly class AddRecipient
{
    public function __construct(
        public string $memberId,
        public string $campaignId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
