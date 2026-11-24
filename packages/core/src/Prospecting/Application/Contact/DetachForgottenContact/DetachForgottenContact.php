<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Contact\DetachForgottenContact;

final readonly class DetachForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
