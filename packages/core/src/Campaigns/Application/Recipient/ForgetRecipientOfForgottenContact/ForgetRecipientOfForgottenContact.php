<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Recipient\ForgetRecipientOfForgottenContact;

final readonly class ForgetRecipientOfForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
