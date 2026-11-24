<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepToDosOfAttachedContact;

final readonly class KeepToDosOfAttachedContact
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $organizationId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
