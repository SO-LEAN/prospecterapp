<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\Connectors\Domain\Model\OrganizationId;
use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * The account does not prospect this organization. The organization may belong to another account, or not exist.
 * The member does not even learn whether it exists.
 */
final class UnknownOrganization extends NotFound
{
    public static function withId(OrganizationId $id): self
    {
        return new self(sprintf('Unknown organization %s', $id->value));
    }
}
