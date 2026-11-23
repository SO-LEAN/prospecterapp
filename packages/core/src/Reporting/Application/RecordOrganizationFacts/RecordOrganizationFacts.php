<?php

declare(strict_types=1);

namespace Prospecter\Core\Reporting\Application\RecordOrganizationFacts;

final readonly class RecordOrganizationFacts
{
    public function __construct(
        public string $accountId,
        public string $organizationId,
        public int $revision,
        public bool $present,
        public bool $located,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
