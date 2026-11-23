<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordContactFacts;

final readonly class RecordContactFacts
{
    public function __construct(
        public string $accountId,
        public string $contactId,
        public int $revision,
        public bool $present,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
