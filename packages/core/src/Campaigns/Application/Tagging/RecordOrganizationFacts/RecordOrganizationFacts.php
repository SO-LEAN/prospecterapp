<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\RecordOrganizationFacts;

final readonly class RecordOrganizationFacts
{
    /**
     * @param list<string> $contacts the persons the organization names as its contacts
     */
    public function __construct(
        public string $accountId,
        public string $organizationId,
        public int $revision,
        public bool $present,
        public array $contacts,
        public ?string $municipalityCode,
        public ?string $country,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
