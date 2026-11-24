<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\RecordContactFacts;

final readonly class RecordContactFacts
{
    public function __construct(
        public string $accountId,
        public string $contactId,
        public int $revision,
        public bool $present,
        public bool $hasEmail,
        public bool $hasPhone,
        public bool $hasMobile,
        public bool $hasLinkedIn,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
