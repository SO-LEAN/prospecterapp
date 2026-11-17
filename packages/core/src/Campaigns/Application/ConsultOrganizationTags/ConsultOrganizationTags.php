<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Application\ConsultOrganizationTags;

final readonly class ConsultOrganizationTags
{
    public function __construct(
        public string $memberId,
        public string $organizationId,
    ) {
    }
}
