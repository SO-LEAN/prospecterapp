<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;
use Prospecter\Core\SharedKernel\Domain\Identity\Uuid;

/** The account does not prospect this organization, so none of its members tags it. */
final class UnknownOrganization extends NotFound
{
    public static function identifiedBy(Uuid $id): self
    {
        return new self(sprintf('Organization %s not found', $id));
    }
}
