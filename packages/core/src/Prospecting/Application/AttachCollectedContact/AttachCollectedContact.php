<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachCollectedContact;

final readonly class AttachCollectedContact
{
    /**
     * @param list<string> $organizationIds
     */
    public function __construct(
        public string $memberId,
        public string $contactId,
        public array $organizationIds,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
