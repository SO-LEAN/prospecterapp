<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\Exchange\ForgetFingerprintsOfForgottenContact;

final readonly class ForgetFingerprintsOfForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
