<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\EraseToDosOfForgottenContact;

final readonly class EraseToDosOfForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $memberId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
