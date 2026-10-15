<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ReviseMyOrganizationIdentity;

final readonly class ReviseMyOrganizationIdentity
{
    public function __construct(
        public string $memberId,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
