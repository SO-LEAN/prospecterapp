<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepExchangesOfAttachedContact;

final readonly class KeepExchangesOfAttachedContact
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
