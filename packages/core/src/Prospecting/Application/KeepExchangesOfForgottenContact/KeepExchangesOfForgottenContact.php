<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\KeepExchangesOfForgottenContact;

final readonly class KeepExchangesOfForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
