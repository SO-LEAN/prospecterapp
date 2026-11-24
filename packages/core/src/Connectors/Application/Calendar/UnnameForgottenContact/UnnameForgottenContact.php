<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Application\Calendar\UnnameForgottenContact;

final readonly class UnnameForgottenContact
{
    public function __construct(
        public string $accountId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
