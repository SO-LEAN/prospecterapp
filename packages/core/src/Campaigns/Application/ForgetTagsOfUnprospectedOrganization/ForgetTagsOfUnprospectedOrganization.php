<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ForgetTagsOfUnprospectedOrganization;

final readonly class ForgetTagsOfUnprospectedOrganization
{
    public function __construct(
        public string $accountId,
        public string $organizationId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
