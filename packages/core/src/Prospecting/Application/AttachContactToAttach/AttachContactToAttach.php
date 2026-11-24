<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\AttachContactToAttach;

final readonly class AttachContactToAttach
{
    public function __construct(
        public string $memberId,
        public string $organizationId,
        public string $contactId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
