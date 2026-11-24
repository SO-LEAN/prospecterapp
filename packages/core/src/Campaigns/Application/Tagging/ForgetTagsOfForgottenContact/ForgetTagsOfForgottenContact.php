<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\Tagging\ForgetTagsOfForgottenContact;

final readonly class ForgetTagsOfForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
