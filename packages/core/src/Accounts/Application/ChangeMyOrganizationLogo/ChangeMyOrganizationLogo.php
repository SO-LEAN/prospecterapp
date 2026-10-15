<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Application\ChangeMyOrganizationLogo;

final readonly class ChangeMyOrganizationLogo
{
    public function __construct(
        public string $memberId,
        public \SplFileInfo $logo,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
