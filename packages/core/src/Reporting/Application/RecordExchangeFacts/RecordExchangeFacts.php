<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordExchangeFacts;

final readonly class RecordExchangeFacts
{
    public function __construct(
        public string $accountId,
        public string $exchangeId,
        public int $revision,
        public bool $present,
        public ?string $status,
        public ?\DateTimeImmutable $notedAt,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
