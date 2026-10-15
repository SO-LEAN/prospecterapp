<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\OrganizationId;

final class UnknownOrganization extends NotFound
{
    public static function identifiedBy(OrganizationId $id): self
    {
        return new self(sprintf('Organization %s not found', $id));
    }
}
