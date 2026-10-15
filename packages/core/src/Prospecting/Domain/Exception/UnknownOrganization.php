<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Exception;

use Prospecter\Core\Prospecting\Domain\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

final class UnknownOrganization extends NotFound
{
    public static function identifiedBy(OrganizationId $id): self
    {
        return new self(sprintf('Organization %s not found', $id));
    }
}
